<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ExternalSources\Jobs\RunExternalSourceImportJob;
use App\Security\OutboundRequestGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Lootwright\Application\ExternalSources\DTO\StagedSourceRecord;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapter;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;
use Lootwright\Application\ExternalSources\Ports\SourceImportStaging;
use Lootwright\Application\ExternalSources\Ports\SourceRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;
use Tests\TestCase;

final class ExternalSourceArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private const REVISION = '8bd138b32ea2631455cac5935bfab089f826094f';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Http::preventStrayRequests();
    }

    public function test_registry_and_fixed_adapter_catalog_fail_closed(): void
    {
        $registry = $this->app->make(SourceRegistry::class);
        $poeNinja = $registry->find('POENINJA-ECONOMY-001');
        self::assertNotNull($poeNinja);
        self::assertFalse($poeNinja->enabled);
        self::assertSame([GameEdition::Poe1], $poeNinja->editions);
        self::assertSame('bounded', $poeNinja->cacheStorageStatus);
        self::assertSame('documented_public_api', $poeNinja->technicalAccess);
        self::assertSame('market_observation', $poeNinja->dataQualityStatus);
        self::assertSame('approved_with_limits', $poeNinja->provenanceStatus);
        self::assertContains('live_fetch:poe_ninja.economy.leagues.fetch', $poeNinja->allowedCapabilities);
        self::assertContains('live_fetch:poe_ninja.builds.fetch', $poeNinja->forbiddenCapabilities);

        $catalog = $this->app->make(ExternalSourceAdapterCatalog::class);
        foreach (['GGG-DOCUMENTED-API', 'POEWIKI-CARGO-001', 'POE2-DATASET-CANDIDATE', 'GGG-POE1-ATLASTREE-001', 'REPOE-CANDIDATE', 'POE-DB-CANDIDATE', 'CRAFT-OF-EXILE-CANDIDATE', 'POE-TRADE-VOCABULARY-CANDIDATE'] as $sourceCode) {
            $adapter = $catalog->find($sourceCode);
            self::assertNotNull($adapter);
            if ($sourceCode === 'POE2-DATASET-CANDIDATE') {
                self::assertTrue($adapter->status()->operational);
                self::assertTrue($adapter->import()->success);
            } else {
                self::assertFalse($adapter->status()->operational);
                $this->expectDisabledImport($adapter);
            }
        }
        Http::assertNothingSent();
    }

    public function test_passive_tree_import_stages_before_immutable_snapshot_approval(): void
    {
        $fixture = base_path('tests/Fixtures/ggg/passive-tree-8bd138b-reduced.json');
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $fixture));

        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $fixture]));
        $report = DB::table('source_import_reports')->sole();
        self::assertSame('approved', $report->status);
        self::assertSame(13, $report->records_received);
        self::assertSame(13, $report->records_imported);
        self::assertSame(DB::table('source_snapshots')->value('id'), $report->source_snapshot_id);
        $this->assertDatabaseCount('source_import_staging_records', 13);
        $this->assertDatabaseMissing('source_import_staging_records', ['status' => 'staged']);

        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $fixture]));
        $this->assertDatabaseCount('source_import_reports', 1);
        $this->assertDatabaseCount('source_snapshots', 1);
    }

    public function test_staging_rollback_is_policy_gated_and_cannot_mutate_an_approved_snapshot(): void
    {
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        $checksum = str_repeat('a', 64);
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', $checksum);
        $staging = $this->app->make(SourceImportStaging::class);
        $recordPayload = ['kind' => 'passive_node', 'id' => '1'];
        $recordChecksum = hash('sha256', json_encode($recordPayload, JSON_THROW_ON_ERROR));
        $report = $staging->stage(
            'GGG-POE1-SKILLTREE-001', self::REVISION, 'ggg.poe1.skilltree.snapshot.import', GameEdition::Poe1,
            $checksum, str_repeat('b', 64), 'test',
            [new StagedSourceRecord('passive:1', $recordChecksum, 'staged', null, $recordPayload)],
            ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'],
        );
        $rollbackId = $staging->rollback($report->id, 'operator');
        self::assertNotSame($report->id, $rollbackId);
        $this->assertDatabaseHas('source_import_reports', ['id' => $report->id, 'status' => 'rolled_back']);
        $this->assertDatabaseHas('source_import_staging_records', ['import_report_id' => $report->id, 'status' => 'rolled_back', 'normalized_payload' => null]);

        $fixture = base_path('tests/Fixtures/ggg/passive-tree-8bd138b-reduced.json');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $fixture));
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $fixture]));
        $approved = DB::table('source_import_reports')->where('status', 'approved')->sole();
        $this->expectException(\DomainException::class);
        $staging->rollback((string) $approved->id, 'operator');
    }

    public function test_poe_ninja_mock_sync_stages_each_allowlisted_category_before_quote_publication(): void
    {
        Config::set('external-sources.poe_ninja.enabled', true);
        Config::set('external-sources.poe_ninja.contact', 'operator@example.test');
        Config::set('source-governance.poeninja_economy_enabled', true);
        Config::set('security.outbound.enabled', true);
        $this->app->forgetInstance(OutboundRequestGuard::class);
        $this->app->singleton(OutboundRequestGuard::class, static fn (): OutboundRequestGuard => new OutboundRequestGuard(
            true,
            (array) config('security.outbound.targets'),
            static fn (string $_host): array => ['93.184.216.34'],
        ));
        Http::fake(static function (Request $request) {
            if (str_ends_with($request->url(), '/poe1/api/economy/leagues')) {
                return Http::response(['leagues' => [['name' => 'Mirage', 'isActive' => true]]], 200);
            }

            return Http::response(['lines' => [[
                'name' => 'Fixture reference',
                'detailsId' => 'fixture-reference',
                'chaosValue' => 1.25,
                'icon' => 'https://example.test/must-not-persist.png',
            ]]], 200, ['ETag' => '"fixture"', 'Cache-Control' => 'max-age=1200']);
        });

        self::assertSame(0, Artisan::call('lootwright:sources:sync-poe-ninja'));
        $this->assertDatabaseCount('source_import_reports', 20);
        $this->assertDatabaseCount('source_snapshots', 20);
        $this->assertDatabaseCount('economy_quotes', 20);
        $this->assertDatabaseMissing('source_import_reports', ['status' => 'staged']);
        self::assertSame(20, DB::table('source_import_reports')->where('status', 'approved')->count());
        self::assertFalse(DB::table('economy_quotes')->get()->contains(
            static fn (object $quote): bool => str_contains(json_encode(get_object_vars($quote), JSON_THROW_ON_ERROR), 'must-not-persist'),
        ));
        self::assertSame(0, Artisan::call('lootwright:sources:sync-poe-ninja'));
        $this->assertDatabaseCount('source_import_reports', 20);
        $this->assertDatabaseCount('source_snapshots', 20);
        $this->assertDatabaseCount('economy_quotes', 20);
        Http::assertSentCount(42);
    }

    public function test_only_super_admin_with_recent_password_can_queue_a_fixed_operational_import(): void
    {
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        Config::set('queue.default', 'database');
        Queue::fake();
        $superAdmin = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);

        $this->actingAs($superAdmin)->post('/admin/sources/import', [
            'source_code' => 'GGG-POE1-SKILLTREE-001',
            'reason' => 'Reviewed ruleset maintenance import.',
        ])->assertRedirect('/user/confirm-password');

        $this->actingAs($superAdmin)->withSession(['auth.password_confirmed_at' => time()])->post('/admin/sources/import', [
            'source_code' => 'GGG-POE1-SKILLTREE-001',
            'reason' => 'Reviewed ruleset maintenance import.',
        ])->assertRedirect();
        Queue::assertPushed(RunExternalSourceImportJob::class, static fn (RunExternalSourceImportJob $job): bool => $job->sourceCode === 'GGG-POE1-SKILLTREE-001');
        $this->assertDatabaseHas('admin_audit_logs', ['actor_user_id' => $superAdmin->id, 'action' => 'external_source.import_requested']);

        $admin = User::factory()->admin()->create(['two_factor_confirmed_at' => now()]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post('/admin/sources/import', [
            'source_code' => 'GGG-POE1-SKILLTREE-001',
            'reason' => 'An admin must not queue this import.',
        ])->assertForbidden();
    }

    private function expectDisabledImport(ExternalSourceAdapter $adapter): void
    {
        try {
            $adapter->import();
            self::fail('A disabled adapter must not execute.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
    }
}
