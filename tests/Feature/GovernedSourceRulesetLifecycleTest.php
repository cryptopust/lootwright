<?php

namespace Tests\Feature;

use DateTimeImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\Rulesets\DTO\RulesetPublication;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Application\Rulesets\Services\GovernedRulesetLifecycle;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Tests\Support\DomainFixtures;
use Tests\TestCase;

final class GovernedSourceRulesetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_required_source_definitions_are_seeded_with_fail_closed_statuses(): void
    {
        foreach ([
            'USER-POB-001' => ['allowed', true, 'active'],
            'USER-ITEM-TEXT-001' => ['allowed', true, 'active'],
            'GGG-POE1-SKILLTREE-001' => ['allowed', true, 'active'],
            'GGG-POE1-ATLASTREE-001' => ['allowed', false, 'outside_mvp'],
            'POEWIKI-CARGO-001' => ['conditional', false, 'candidate'],
            'POENINJA-ECONOMY-001' => ['conditional', false, 'optional'],
            'REPOE-CANDIDATE' => ['prohibited', false, 'prohibited'],
        ] as $code => [$status, $enabled, $scope]) {
            $this->assertDatabaseHas('policy_data_sources', [
                'id' => $code,
                'governance_status' => $status,
                'enabled_by_default' => $enabled,
                'mvp_scope' => $scope,
            ]);
        }
    }

    public function test_same_content_checksum_is_idempotent_and_revision_conflicts_are_quarantined(): void
    {
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);
        $payload = ['nodes' => [['id' => 'poe1:skill:one']]];
        $snapshot = $this->snapshot($payload, 'rev-1');
        $conditions = ['documented_export', 'operator_workflow', 'checksum_verified', 'poe1_scope'];

        $first = $lifecycle->import($snapshot, $conditions);
        $replay = $lifecycle->import($snapshot, $conditions);
        $conflict = $lifecycle->import($this->snapshot(['nodes' => [['id' => 'poe1:skill:changed']]], 'rev-1'), $conditions);

        self::assertFalse($first->replayed);
        self::assertTrue($replay->replayed);
        self::assertSame($first->snapshotId, $replay->snapshotId);
        self::assertSame('quarantined', $conflict->status);
        self::assertNull($conflict->snapshotId);
        $this->assertDatabaseCount('source_snapshots', 1);
        $this->assertDatabaseCount('external_source_sync_runs', 3);
        $this->assertDatabaseHas('source_conflicts', [
            'id' => $conflict->conflictId,
            'status' => 'quarantined',
            'reason_code' => 'revision_checksum_conflict',
        ]);
    }

    public function test_published_ruleset_and_source_snapshots_are_database_immutable(): void
    {
        [$snapshotId, $rulesetId] = $this->publishRuleset('1.0.0', ['rule' => ['value' => 1]]);

        foreach ([
            fn (): int => DB::table('source_snapshots')->where('id', $snapshotId)->update(['status' => 'quarantined']),
            fn (): int => DB::table('ruleset_versions')->where('id', $rulesetId)->update(['version' => '1.0.1']),
            fn (): int => DB::table('ruleset_versions')->where('id', $rulesetId)->delete(),
        ] as $mutation) {
            try {
                DB::transaction(static fn (): int => $mutation());
                self::fail('Immutable published data accepted a mutation.');
            } catch (QueryException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function test_ruleset_activation_updates_one_scope_and_appends_history_atomically(): void
    {
        [, $firstId] = $this->publishRuleset('1.0.0', ['rule' => ['value' => 1]]);
        [, $secondId] = $this->publishRuleset('1.0.1', ['rule' => ['value' => 2]], 'rev-2', $firstId);
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);

        $first = $lifecycle->activate($firstId);
        $second = $lifecycle->activate($secondId);

        self::assertSame($first->activationId, $second->activationId);
        self::assertSame($firstId, $second->previousRulesetVersionId);
        $this->assertDatabaseCount('ruleset_activations', 1);
        $this->assertDatabaseCount('ruleset_activation_history', 2);
        $this->assertDatabaseHas('ruleset_activations', ['id' => $first->activationId, 'ruleset_version_id' => $secondId]);

        $resolved = $this->app->make(RulesetResolver::class)->resolve(
            GameEdition::Poe1,
            DomainFixtures::patch(GameEdition::Poe1, '3.28.0'),
            null,
            DomainFixtures::parser(GameEdition::Poe1),
        );
        self::assertTrue($resolved->isSuccess());
        self::assertInstanceOf(RulesetIdentity::class, $resolved->value());
        self::assertSame($secondId, $resolved->value()->id->value);

        $wrongPatch = $this->app->make(RulesetResolver::class)->resolve(
            GameEdition::Poe1,
            DomainFixtures::patch(GameEdition::Poe1, '3.27.0'),
            null,
            DomainFixtures::parser(GameEdition::Poe1),
        );
        $wrongParser = $this->app->make(RulesetResolver::class)->resolve(
            GameEdition::Poe1,
            DomainFixtures::patch(GameEdition::Poe1, '3.28.0'),
            null,
            DomainFixtures::parser(GameEdition::Poe1, '2.0.0'),
        );
        self::assertTrue($wrongPatch->isFailure());
        self::assertTrue($wrongParser->isFailure());
    }

    public function test_central_policy_rejects_every_disabled_or_prohibited_runtime_source(): void
    {
        Config::set('source-governance.poewiki_import_enabled', false);
        Config::set('source-governance.poeninja_economy_enabled', false);
        $policy = $this->app->make(SourceGovernancePolicy::class);

        foreach ([
            ['POEWIKI-CARGO-001', 'candidate-2026-08-20', 'poewiki.cargo.snapshot.import'],
            ['POENINJA-ECONOMY-001', 'economy-v1', 'poeninja.economy.snapshot.import'],
            ['REPOE-CANDIDATE', 'unreviewed-2026-08-14', 'repoe.snapshot.import'],
            ['GGG-POE1-ATLASTREE-001', '1.0.0', 'ggg.poe1.atlastree.snapshot.import'],
        ] as [$source, $version, $operation]) {
            self::assertFalse($policy->permitsImport($source, $version, $operation, []), $source.' unexpectedly became executable.');
            self::assertFalse($policy->permitsActivation($source, $version), $source.' unexpectedly became ruleset authority.');
        }
    }

    public function test_ruleset_cannot_activate_when_a_source_switch_or_policy_denies_authority(): void
    {
        Config::set('source-governance.poeninja_economy_enabled', true);
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);
        $payload = ['quotes' => [['category' => 'Currency']]];
        $snapshot = new SourceSnapshotImport(
            'POENINJA-ECONOMY-001',
            'economy-v1',
            GameEdition::Poe1,
            'poeninja.economy.snapshot.import',
            'https://poe.ninja/docs/api',
            'economy-rev-1',
            new DateTimeImmutable('2026-08-20T09:00:00Z'),
            hash('sha256', CanonicalJson::encode($payload)),
            'application/json',
            'Proprietary-Documented-API',
            '1.0.0',
            $payload,
        );
        $record = $lifecycle->import($snapshot, ['operator_contact_configured', 'exact_endpoint_allowlist', 'normalized_snapshot_only']);
        $rules = ['invalid_market_authority' => true];
        $rulesetId = (string) Str::uuid7();
        $lifecycle->publish(new RulesetPublication(
            $rulesetId,
            GameEdition::Poe1,
            '9.0.0',
            '3.28.0',
            null,
            '1.0.0',
            hash('sha256', CanonicalJson::encode($rules)),
            '1.0.0',
            [$record->snapshotId],
            $rules,
            new DateTimeImmutable('2026-08-20T09:05:00Z'),
        ));

        $this->expectException(DomainException::class);
        $lifecycle->activate($rulesetId);
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(array $payload, string $revision): SourceSnapshotImport
    {
        return new SourceSnapshotImport(
            'GGG-POE1-SKILLTREE-001',
            '1.0.0',
            GameEdition::Poe1,
            'ggg.poe1.skilltree.snapshot.import',
            'https://www.pathofexile.com/developer/docs/reference',
            $revision,
            new DateTimeImmutable('2026-08-20T08:00:00Z'),
            hash('sha256', CanonicalJson::encode($payload)),
            'application/json',
            'GGG-Developer-Terms',
            '1.0.0',
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{string, string}
     */
    private function publishRuleset(string $version, array $payload, string $revision = 'rev-1', ?string $supersedes = null): array
    {
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);
        $conditions = ['documented_export', 'operator_workflow', 'checksum_verified', 'poe1_scope'];
        $snapshot = $lifecycle->import($this->snapshot($payload, $revision), $conditions);
        self::assertNotNull($snapshot->snapshotId);
        $rulesetId = (string) Str::uuid7();
        $lifecycle->publish(new RulesetPublication(
            $rulesetId,
            GameEdition::Poe1,
            $version,
            '3.28.0',
            null,
            '1.0.0',
            hash('sha256', CanonicalJson::encode($payload)),
            '1.0.0',
            [$snapshot->snapshotId],
            $payload,
            new DateTimeImmutable('2026-08-20T08:05:00Z'),
            $supersedes,
        ));

        return [$snapshot->snapshotId, $rulesetId];
    }
}
