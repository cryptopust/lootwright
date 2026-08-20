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
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\Ports\ActiveRulesetResolver;
use Lootwright\Domain\Rulesets\Ports\RulesetRepository;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Tests\Support\DomainFixtures;
use Tests\TestCase;

final class GovernedSourceRulesetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const SKILL_TREE_REVISION = '8bd138b32ea2631455cac5935bfab089f826094f';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Config::set('source-governance.ggg_passive_tree.enabled', true);
    }

    public function test_required_source_definitions_are_seeded_with_fail_closed_statuses(): void
    {
        foreach ([
            'USER-POB-001' => ['allowed', true, 'active'],
            'USER-ITEM-TEXT-001' => ['allowed', true, 'active'],
            'GGG-POE1-SKILLTREE-001' => ['allowed', false, 'active'],
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
        $conditions = ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'];

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

    public function test_fixture_ruleset_is_published_for_tests_but_cannot_become_active_authority(): void
    {
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);
        $payload = ['fixture' => true];
        $snapshot = $lifecycle->import($this->snapshot($payload, 'fixture-rev'), ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope']);
        $id = (string) Str::uuid7();
        $lifecycle->publish(new RulesetPublication(
            $id,
            GameEdition::Poe1,
            '8.0.0',
            '3.28.0',
            null,
            '1.0.0',
            hash('sha256', CanonicalJson::encode($payload)),
            '1.0.0',
            [$snapshot->snapshotId],
            $payload,
            new DateTimeImmutable('2026-08-20T10:00:00Z'),
            datasetClassification: DatasetClassification::Fixture,
            provenanceStatus: ProvenanceStatus::Approved,
            compatibilityStatus: RulesetCompatibilityStatus::Compatible,
        ));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Fixture');
        $lifecycle->activate($id);
    }

    public function test_checksum_mismatch_and_invalid_provenance_fail_closed(): void
    {
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);
        $payload = ['rule' => ['value' => 1]];
        $snapshot = $lifecycle->import($this->snapshot($payload, 'invalid-provenance-rev'), ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope']);

        try {
            $lifecycle->publish(new RulesetPublication(
                (string) Str::uuid7(), GameEdition::Poe1, '7.0.0', '3.28.0', null, '1.0.0',
                str_repeat('0', 64), '1.0.0', [$snapshot->snapshotId], $payload,
                new DateTimeImmutable('2026-08-20T10:10:00Z'),
            ));
            self::fail('A mismatched ruleset checksum must be rejected.');
        } catch (DomainException $exception) {
            self::assertStringContainsString('checksum', $exception->getMessage());
        }

        $id = (string) Str::uuid7();
        $lifecycle->publish(new RulesetPublication(
            $id, GameEdition::Poe1, '7.0.1', '3.28.0', null, '1.0.0',
            hash('sha256', CanonicalJson::encode($payload)), '1.0.0', [$snapshot->snapshotId], $payload,
            new DateTimeImmutable('2026-08-20T10:11:00Z'),
            datasetClassification: DatasetClassification::ApprovedImport,
            provenanceStatus: ProvenanceStatus::Invalid,
            compatibilityStatus: RulesetCompatibilityStatus::InvalidProvenance,
        ));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('unapproved');
        $lifecycle->activate($id);
    }

    public function test_historical_rulesets_remain_retrievable_and_patch_failures_are_explicit(): void
    {
        [, $firstId] = $this->publishRuleset('1.0.0', ['rule' => ['value' => 1]]);
        [, $secondId] = $this->publishRuleset('1.0.1', ['rule' => ['value' => 2]], 'rev-history-2', $firstId);
        $lifecycle = $this->app->make(GovernedRulesetLifecycle::class);
        $lifecycle->activate($firstId);
        $lifecycle->activate($secondId);

        $repository = $this->app->make(RulesetRepository::class);
        self::assertSame($firstId, $repository->findById($firstId)?->identity->id->value);
        self::assertSame($secondId, $repository->findByVersion(GameEdition::Poe1, '1.0.1')?->identity->id->value);

        $resolver = $this->app->make(ActiveRulesetResolver::class);
        $unsupported = $resolver->resolveActive(
            GameEdition::Poe1,
            DomainFixtures::patch(GameEdition::Poe1, '3.27.0'),
            null,
            DomainFixtures::parser(GameEdition::Poe1),
        );
        $poe2Unavailable = $resolver->resolveActive(
            GameEdition::Poe2,
            DomainFixtures::patch(GameEdition::Poe2, '0.5.0'),
            null,
            DomainFixtures::parser(GameEdition::Poe2),
        );
        self::assertSame(RulesetCompatibilityStatus::UnsupportedPatch, $unsupported->status);
        self::assertSame(RulesetCompatibilityStatus::Unavailable, $poe2Unavailable->status);
        self::assertNull($poe2Unavailable->ruleset);
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(array $payload, string $revision): SourceSnapshotImport
    {
        return new SourceSnapshotImport(
            'GGG-POE1-SKILLTREE-001',
            self::SKILL_TREE_REVISION,
            GameEdition::Poe1,
            'ggg.poe1.skilltree.snapshot.import',
            'https://raw.githubusercontent.com/grindinggear/skilltree-export/'.self::SKILL_TREE_REVISION.'/data.json',
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
        $conditions = ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'];
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
            DatasetClassification::ApprovedImport,
            ProvenanceStatus::Approved,
            RulesetCompatibilityStatus::Compatible,
        ));

        return [$snapshot->snapshotId, $rulesetId];
    }
}
