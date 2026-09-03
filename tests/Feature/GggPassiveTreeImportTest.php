<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Rulesets\PassiveTree\GggPassiveTreeUrl;
use App\Security\OutboundRequestGuard;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Shared\Game\GameEdition;
use Tests\TestCase;

final class GggPassiveTreeImportTest extends TestCase
{
    use RefreshDatabase;

    private const REVISION = '8bd138b32ea2631455cac5935bfab089f826094f';

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->fixture = base_path('tests/Fixtures/ggg/passive-tree-8bd138b-reduced.json');
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $this->fixture));
        Config::set('security.outbound.enabled', true);
        Http::preventStrayRequests();
    }

    public function test_file_import_normalizes_and_stores_an_immutable_snapshot(): void
    {
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $this->fixture]));
        self::assertStringContainsString(self::REVISION, Artisan::output());
        $snapshot = DB::table('source_snapshots')->sole();
        $payload = json_decode($snapshot->normalized_payload, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('poe1', $payload['provenance']['game']);
        self::assertSame(7, count($payload['passive_tree']['classes']));
        self::assertSame(6, count($payload['passive_tree']['nodes']));
        self::assertSame('keystone', $this->node($payload, '58556')['node_type']);
        self::assertSame('mastery', $this->node($payload, '44298')['node_type']);
        self::assertSame('Deadeye', $this->node($payload, '53086')['ascendancy']);
        self::assertSame('Ranger', $this->node($payload, '53086')['class']);
        self::assertSame('secondary', $this->node($payload, '28609')['progression_type']);
        self::assertSame('Art/2DArt/SkillIcons/passives/EnergisedFortress.png', $this->node($payload, '58556')['icon_path']);
        self::assertStringNotContainsString('flavourText', $snapshot->normalized_payload);
        $this->assertDatabaseHas('external_source_sync_runs', [
            'source_key' => 'GGG-POE1-SKILLTREE-001',
            'source_version' => self::REVISION,
            'operation' => 'ggg.poe1.skilltree.snapshot.import',
            'response_checksum_sha256' => hash_file('sha256', $this->fixture),
            'status' => 'succeeded',
        ]);
    }

    public function test_generic_candidate_command_publishes_without_activation(): void
    {
        self::assertSame(0, Artisan::call('lootwright:ruleset:candidate', ['--file' => $this->fixture]), Artisan::output());
        $this->assertDatabaseCount('ruleset_versions', 1);
        $this->assertDatabaseCount('ruleset_activations', 0);
    }

    public function test_allowlisted_url_uses_identifying_headers_and_never_follows_redirects(): void
    {
        $url = GggPassiveTreeUrl::forRevision(self::REVISION);
        $body = file_get_contents($this->fixture);
        self::assertIsString($body);
        Http::fake([$url => Http::sequence()
            ->push($body, 200, ['Content-Type' => 'application/json'])
            ->push('', 302, ['Location' => 'https://example.test/data.json'])]);
        $this->publicDns();

        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--url' => $url, '--dry-run' => true]), Artisan::output());
        $this->assertDatabaseCount('source_snapshots', 0);
        Http::assertSent(static fn (Request $request): bool => $request->url() === $url
            && $request->hasHeader('Accept', 'application/json')
            && str_contains($request->header('User-Agent')[0] ?? '', 'Lootwright/0.1.0')
            && str_contains($request->header('User-Agent')[0] ?? '', 'operator@example.test'));

        self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--url' => $url]));
        self::assertStringContainsString('Redirects are denied', Artisan::output());
    }

    public function test_wrong_hosts_userinfo_ports_branches_and_private_dns_are_denied_before_http(): void
    {
        foreach ([
            'https://evil.example/grindinggear/skilltree-export/'.self::REVISION.'/data.json',
            'https://user@raw.githubusercontent.com/grindinggear/skilltree-export/'.self::REVISION.'/data.json',
            'https://raw.githubusercontent.com:444/grindinggear/skilltree-export/'.self::REVISION.'/data.json',
            'https://raw.githubusercontent.com/grindinggear/skilltree-export/master/data.json',
        ] as $url) {
            self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--url' => $url]));
        }
        Http::assertNothingSent();

        $this->app->forgetInstance(OutboundRequestGuard::class);
        $this->app->singleton(OutboundRequestGuard::class, static fn (): OutboundRequestGuard => new OutboundRequestGuard(
            true,
            (array) config('security.outbound.targets'),
            static fn (string $_host): array => ['127.0.0.1', '169.254.169.254'],
        ));
        self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--url' => GggPassiveTreeUrl::forRevision(self::REVISION)]));
        self::assertStringContainsString('non-public address', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_invalid_schema_is_quarantined_without_storing_the_raw_body(): void
    {
        $invalid = tempnam(sys_get_temp_dir(), 'lootwright-invalid-tree-');
        self::assertIsString($invalid);
        file_put_contents($invalid, '{"tree":"Default","classes":[],"nodes":{}}');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $invalid));
        try {
            self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--file' => $invalid]));
            self::assertStringContainsString('quarantined', Artisan::output());
            $this->assertDatabaseCount('source_snapshots', 1);
            $this->assertDatabaseHas('source_snapshots', ['status' => 'rejected', 'upstream_checksum_sha256' => hash_file('sha256', $invalid)]);
            $snapshot = DB::table('source_snapshots')->sole();
            self::assertStringNotContainsString('{"tree"', $snapshot->normalized_payload);
            $this->assertDatabaseHas('source_conflicts', ['reason_code' => 'unexpected_schema', 'status' => 'quarantined']);
            $run = DB::table('external_source_sync_runs')->sole();
            self::assertSame('ggg.poe1.skilltree.snapshot.quarantine', $run->operation);
            self::assertSame(hash_file('sha256', $invalid), $run->response_checksum_sha256);
            self::assertFalse(str_contains(json_encode(get_object_vars($run), JSON_THROW_ON_ERROR), '{"tree"'));
        } finally {
            unlink($invalid);
        }
    }

    public function test_invalid_dry_run_is_rejected_without_lifecycle_writes(): void
    {
        $invalid = tempnam(sys_get_temp_dir(), 'lootwright-invalid-tree-dry-');
        self::assertIsString($invalid);
        file_put_contents($invalid, '{"tree":"Default","classes":[],"nodes":{}}');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $invalid));
        try {
            self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--file' => $invalid, '--dry-run' => true]));
            self::assertStringContainsString('rejected', Artisan::output());
            $this->assertDatabaseCount('external_source_sync_runs', 0);
            $this->assertDatabaseCount('source_snapshots', 0);
            $this->assertDatabaseCount('source_conflicts', 0);
        } finally {
            unlink($invalid);
        }
    }

    public function test_http_content_length_over_the_bound_is_rejected_without_persistence(): void
    {
        $url = GggPassiveTreeUrl::forRevision(self::REVISION);
        Http::fake([$url => Http::response('{}', 200, ['Content-Length' => '8000001'])]);
        $this->publicDns();

        self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--url' => $url]));
        self::assertStringContainsString('size limit', Artisan::output());
        $this->assertDatabaseCount('external_source_sync_runs', 0);
        $this->assertDatabaseCount('source_snapshots', 0);
    }

    public function test_duplicate_import_is_idempotent_and_failed_activation_preserves_active_ruleset(): void
    {
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $this->fixture, '--activate' => true]));
        $activeId = DB::table('ruleset_activations')->value('ruleset_version_id');
        self::assertIsString($activeId);
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $this->fixture, '--activate' => true]));
        self::assertStringContainsString('yes', Artisan::output());
        $this->assertDatabaseCount('source_snapshots', 1);
        $this->assertDatabaseCount('ruleset_versions', 1);
        $this->assertDatabaseHas('ruleset_dataset_approvals', [
            'ruleset_version_id' => $activeId,
            'game_edition' => 'poe1',
            'dataset_classification' => 'approved_import',
            'provenance_status' => 'approved',
            'compatibility_status' => 'compatible',
        ]);
        $this->assertDatabaseCount('canonical_game_data', 23);
        $this->assertDatabaseHas('canonical_game_data', ['game_edition' => 'poe1', 'entity_type' => 'character_class', 'external_id' => 'class:0']);
        $this->assertDatabaseHas('canonical_game_data', ['game_edition' => 'poe1', 'entity_type' => 'ascendancy', 'parent_entity_type' => 'character_class']);
        $this->assertDatabaseHas('canonical_game_data', ['game_edition' => 'poe1', 'entity_type' => 'keystone', 'external_id' => 'passive:58556']);
        $this->assertDatabaseMissing('canonical_game_data', ['game_edition' => 'poe2']);
        $this->assertDatabaseMissing('canonical_game_data', ['entity_type' => 'skill_gem']);

        $repository = $this->app->make(GameDataRepository::class);
        self::assertCount(7, $repository->listForRuleset(GameEdition::Poe1, $activeId, CanonicalEntityType::CharacterClass));
        self::assertNull($repository->find(GameEdition::Poe2, $activeId, CanonicalEntityType::CharacterClass, 'class:0'));
        $this->assertDatabaseCount('ruleset_activations', 1);
        self::assertSame($activeId, DB::table('ruleset_activations')->value('ruleset_version_id'));

        $invalid = tempnam(sys_get_temp_dir(), 'lootwright-bad-tree-');
        self::assertIsString($invalid);
        file_put_contents($invalid, '{"invalid":true}');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $invalid));
        try {
            self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--file' => $invalid, '--activate' => true]));
            self::assertSame($activeId, DB::table('ruleset_activations')->value('ruleset_version_id'));
            $this->assertDatabaseCount('ruleset_activation_history', 1);
        } finally {
            unlink($invalid);
        }
    }

    public function test_disabled_switch_missing_contact_and_unapproved_commit_fail_closed(): void
    {
        Config::set('source-governance.ggg_passive_tree.enabled', false);
        self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--file' => $this->fixture]));
        $this->assertDatabaseCount('external_source_sync_runs', 0);

        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', '');
        self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--url' => GggPassiveTreeUrl::forRevision(self::REVISION)]));
        self::assertStringContainsString('CONTACT is required', Artisan::output());
        Http::assertNothingSent();

        $unknown = str_repeat('a', 40);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        self::assertSame(1, Artisan::call('poe:import-passive-tree', ['--url' => GggPassiveTreeUrl::forRevision($unknown)]));
        self::assertStringContainsString('not approved', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_database_rejects_cross_edition_canonical_relationships(): void
    {
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $this->fixture, '--activate' => true]));
        $rulesetId = DB::table('ruleset_versions')->value('id');
        $snapshotId = DB::table('source_snapshots')->value('id');

        try {
            DB::table('canonical_game_data')->insert([
                'id' => '11111111-1111-4111-8111-111111111111',
                'ruleset_version_id' => $rulesetId,
                'game_edition' => 'poe2',
                'entity_type' => 'character_class',
                'external_id' => 'class:0',
                'display_name' => 'Cross-edition invalid row',
                'source_snapshot_id' => $snapshotId,
                'payload' => '{}',
                'payload_checksum_sha256' => hash('sha256', '{}'),
                'created_at' => now(),
            ]);
            self::fail('A PoE2 canonical row must not reference PoE1 ruleset or source identities.');
        } catch (QueryException) {
            self::addToAssertionCount(1);
        }
    }

    public function test_authorized_admin_can_inspect_ruleset_provenance_without_canonical_payloads(): void
    {
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $this->fixture, '--activate' => true]));
        $admin = User::factory()->admin()->create(['two_factor_confirmed_at' => now()]);
        Config::set('inertia.ssr.enabled', false);

        $this->actingAs($admin)->get('/admin/catalog')->assertOk()->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Admin/Catalog')
                ->where('rulesets.0.game_edition', 'poe1')
                ->where('rulesets.0.dataset_classification', 'approved_import')
                ->where('rulesets.0.provenance_status', 'approved')
                ->where('rulesets.0.compatibility_status', 'compatible')
                ->where('rulesets.0.active', true)
                ->has('rulesets.0.entity_counts')
                ->missing('rulesets.0.canonical_payload')
                ->missing('rulesets.0.normalized_payload'),
        );
    }

    private function publicDns(): void
    {
        $this->app->forgetInstance(OutboundRequestGuard::class);
        $this->app->singleton(OutboundRequestGuard::class, static fn (): OutboundRequestGuard => new OutboundRequestGuard(
            true,
            (array) config('security.outbound.targets'),
            static fn (string $_host): array => ['93.184.216.34'],
        ));
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function node(array $payload, string $id): array
    {
        foreach ($payload['passive_tree']['nodes'] as $node) {
            if ($node['id'] === $id) {
                return $node;
            }
        }

        self::fail("Missing normalized node {$id}.");
    }
}
