<?php

namespace App\Modules\Rulesets;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\Rulesets\DTO\RulesetActivation;
use Lootwright\Application\Rulesets\DTO\RulesetPublication;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotRecord;
use Lootwright\Application\Rulesets\Ports\GovernedRulesetRepository;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use RuntimeException;

final readonly class PostgresGovernedRulesetRepository implements GovernedRulesetRepository
{
    public function __construct(private SourceGovernancePolicy $policy) {}

    public function importSnapshot(SourceSnapshotImport $snapshot): SourceSnapshotRecord
    {
        if (! hash_equals($snapshot->checksumSha256, hash('sha256', CanonicalJson::encode($snapshot->normalizedPayload)))) {
            throw new DomainException('The source snapshot checksum does not match its normalized payload.');
        }

        return DB::transaction(function (Connection $_connection) use ($snapshot): SourceSnapshotRecord {
            $now = CarbonImmutable::now('UTC');
            $runId = (string) Str::uuid7();
            $versionId = $this->sourceVersionId($snapshot->sourceCode, $snapshot->sourceVersion);

            DB::table('external_source_sync_runs')->insert([
                'id' => $runId,
                'policy_source_version_id' => $versionId,
                'source_key' => $snapshot->sourceCode,
                'source_version' => $snapshot->sourceVersion,
                'game_edition' => $snapshot->edition->value,
                'operation' => $snapshot->operation,
                'status' => 'started',
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $existing = DB::table('source_snapshots')
                ->where('source_code', $snapshot->sourceCode)
                ->where('game_edition', $snapshot->edition->value)
                ->where('checksum_sha256', $snapshot->checksumSha256)
                ->first(['id']);

            if ($existing !== null) {
                $snapshotId = $this->property($existing, 'id');
                $this->completeRun($runId, 'succeeded', $snapshotId, $now);

                return new SourceSnapshotRecord($runId, $snapshotId, 'succeeded', true);
            }

            if ($snapshot->upstreamRevision !== null) {
                $revision = DB::table('source_snapshots')
                    ->where('source_code', $snapshot->sourceCode)
                    ->where('game_edition', $snapshot->edition->value)
                    ->where('upstream_revision', $snapshot->upstreamRevision)
                    ->first(['id']);

                if ($revision !== null) {
                    $conflictId = (string) Str::uuid7();
                    $existingSnapshotId = $this->property($revision, 'id');
                    DB::table('source_conflicts')->insert([
                        'id' => $conflictId,
                        'import_run_id' => $runId,
                        'source_version_id' => $versionId,
                        'source_code' => $snapshot->sourceCode,
                        'game_edition' => $snapshot->edition->value,
                        'existing_snapshot_id' => $existingSnapshotId,
                        'upstream_revision' => $snapshot->upstreamRevision,
                        'candidate_checksum_sha256' => $snapshot->checksumSha256,
                        'reason_code' => 'revision_checksum_conflict',
                        'status' => 'quarantined',
                        'detected_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $this->completeRun($runId, 'quarantined', null, $now, 'revision_checksum_conflict');

                    return new SourceSnapshotRecord($runId, null, 'quarantined', false, $conflictId);
                }
            }

            $snapshotId = (string) Str::uuid7();
            DB::table('source_snapshots')->insert([
                'id' => $snapshotId,
                'first_import_run_id' => $runId,
                'source_version_id' => $versionId,
                'source_code' => $snapshot->sourceCode,
                'game_edition' => $snapshot->edition->value,
                'source_url' => $snapshot->sourceUrl,
                'upstream_revision' => $snapshot->upstreamRevision,
                'retrieved_at' => $snapshot->retrievedAt,
                'checksum_sha256' => $snapshot->checksumSha256,
                'content_type' => $snapshot->contentType,
                'license_identifier' => $snapshot->licenseIdentifier,
                'status' => 'valid',
                'schema_version' => $snapshot->schemaVersion,
                'normalized_payload' => CanonicalJson::encode($snapshot->normalizedPayload),
                'created_at' => $now,
            ]);
            $this->completeRun($runId, 'succeeded', $snapshotId, $now);

            return new SourceSnapshotRecord($runId, $snapshotId, 'succeeded', false);
        }, 3);
    }

    public function publish(RulesetPublication $ruleset): void
    {
        if (! hash_equals($ruleset->checksumSha256, hash('sha256', CanonicalJson::encode($ruleset->canonicalPayload)))) {
            throw new DomainException('The ruleset checksum does not match its canonical payload.');
        }

        DB::transaction(function (Connection $_connection) use ($ruleset): void {
            $snapshots = DB::table('source_snapshots')
                ->join('policy_data_sources', 'policy_data_sources.id', '=', 'source_snapshots.source_code')
                ->whereIn('source_snapshots.id', $ruleset->sourceSnapshotIds)
                ->lockForUpdate()
                ->get([
                    'source_snapshots.id',
                    'source_snapshots.game_edition',
                    'source_snapshots.status',
                    'source_snapshots.source_code',
                    'policy_data_sources.governance_status',
                ]);

            if ($snapshots->count() !== count($ruleset->sourceSnapshotIds)) {
                throw new DomainException('Every ruleset source snapshot must exist.');
            }

            foreach ($snapshots as $snapshot) {
                $data = get_object_vars($snapshot);
                $snapshotId = $this->string($data, 'id');

                if ($this->string($data, 'game_edition') !== $ruleset->edition->value
                    || $this->string($data, 'status') !== 'valid'
                    || $this->string($data, 'governance_status') === 'prohibited'
                    || DB::table('source_conflicts')->where('existing_snapshot_id', $snapshotId)->where('status', 'quarantined')->exists()
                ) {
                    throw new DomainException('A ruleset can cite only valid, same-game, non-prohibited snapshots.');
                }
            }

            DB::table('ruleset_versions')->insert([
                'id' => $ruleset->id,
                'game_edition' => $ruleset->edition->value,
                'version' => $ruleset->version,
                'patch' => $ruleset->patch,
                'league' => $ruleset->league,
                'league_key' => $ruleset->league ?? '',
                'parser_version' => $ruleset->parserVersion,
                'checksum_sha256' => $ruleset->checksumSha256,
                'schema_version' => $ruleset->schemaVersion,
                'status' => 'published',
                'canonical_payload' => CanonicalJson::encode($ruleset->canonicalPayload),
                'supersedes_ruleset_version_id' => $ruleset->supersedesRulesetVersionId,
                'published_at' => $ruleset->publishedAt,
                'created_at' => $ruleset->publishedAt,
            ]);

            foreach ($ruleset->sourceSnapshotIds as $snapshotId) {
                DB::table('ruleset_source_snapshots')->insert([
                    'ruleset_version_id' => $ruleset->id,
                    'source_snapshot_id' => $snapshotId,
                    'created_at' => $ruleset->publishedAt,
                ]);
            }
        }, 3);
    }

    public function activate(string $rulesetVersionId, string $actorType = 'operator'): RulesetActivation
    {
        if (preg_match('/^[a-z][a-z0-9_-]{1,31}$/D', $actorType) !== 1) {
            throw new DomainException('Ruleset activation requires a canonical actor type.');
        }

        return DB::transaction(function (Connection $_connection) use ($rulesetVersionId, $actorType): RulesetActivation {
            $row = DB::table('ruleset_versions')->where('id', $rulesetVersionId)->lockForUpdate()->first();

            if ($row === null) {
                throw new DomainException('The requested ruleset version does not exist.');
            }

            $data = get_object_vars($row);

            if ($this->string($data, 'status') !== 'published') {
                throw new DomainException('Only a published ruleset can be activated.');
            }

            $sources = DB::table('ruleset_source_snapshots')
                ->join('source_snapshots', 'source_snapshots.id', '=', 'ruleset_source_snapshots.source_snapshot_id')
                ->join('policy_data_sources', 'policy_data_sources.id', '=', 'source_snapshots.source_code')
                ->where('ruleset_source_snapshots.ruleset_version_id', $rulesetVersionId)
                ->join('policy_data_source_versions', 'policy_data_source_versions.id', '=', 'source_snapshots.source_version_id')
                ->get(['source_snapshots.id', 'source_snapshots.source_code', 'source_snapshots.status', 'policy_data_sources.governance_status', 'policy_data_source_versions.version as source_version']);

            if ($sources->isEmpty()) {
                throw new DomainException('A ruleset without source snapshots cannot be activated.');
            }

            foreach ($sources as $source) {
                $sourceData = get_object_vars($source);
                $snapshotId = $this->string($sourceData, 'id');
                $sourceCode = $this->string($sourceData, 'source_code');
                $sourceVersion = $this->string($sourceData, 'source_version');

                if ($this->string($sourceData, 'status') !== 'valid'
                    || $this->string($sourceData, 'governance_status') === 'prohibited'
                    || DB::table('source_conflicts')->where('existing_snapshot_id', $snapshotId)->where('status', 'quarantined')->exists()
                    || ! $this->policy->permitsActivation($sourceCode, $sourceVersion)
                ) {
                    throw new DomainException('Ruleset activation is denied by source governance.');
                }
            }

            $scope = [
                'game_edition' => $this->string($data, 'game_edition'),
                'patch' => $this->string($data, 'patch'),
                'league_key' => $this->string($data, 'league_key'),
                'parser_version' => $this->string($data, 'parser_version'),
            ];
            $current = DB::table('ruleset_activations')->where($scope)->lockForUpdate()->first();
            $previousId = $current === null ? null : $this->property($current, 'ruleset_version_id');
            $activationId = $current === null ? (string) Str::uuid7() : $this->property($current, 'id');

            if ($previousId === $rulesetVersionId) {
                return $this->activation($activationId, $data, $rulesetVersionId, $previousId);
            }

            $now = CarbonImmutable::now('UTC');

            if ($current === null) {
                DB::table('ruleset_activations')->insert([
                    'id' => $activationId,
                    ...$scope,
                    'ruleset_version_id' => $rulesetVersionId,
                    'activated_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('ruleset_activations')->where('id', $activationId)->update([
                    'ruleset_version_id' => $rulesetVersionId,
                    'activated_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('ruleset_activation_history')->insert([
                'id' => (string) Str::uuid7(),
                'activation_id' => $activationId,
                ...$scope,
                'previous_ruleset_version_id' => $previousId,
                'ruleset_version_id' => $rulesetVersionId,
                'actor_type' => $actorType,
                'activated_at' => $now,
                'created_at' => $now,
            ]);

            return $this->activation($activationId, $data, $rulesetVersionId, $previousId);
        }, 3);
    }

    private function sourceVersionId(string $sourceCode, string $sourceVersion): int
    {
        $id = DB::table('policy_data_source_versions')
            ->where('source_id', $sourceCode)
            ->where('version', $sourceVersion)
            ->value('id');

        if (! is_int($id)) {
            throw new DomainException('The governed source version is not registered.');
        }

        return $id;
    }

    private function completeRun(string $runId, string $status, ?string $snapshotId, CarbonImmutable $now, ?string $failureCode = null): void
    {
        DB::table('external_source_sync_runs')->where('id', $runId)->update([
            'source_snapshot_id' => $snapshotId,
            'status' => $status,
            'failure_code' => $failureCode,
            'completed_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @param array<string, mixed> $ruleset */
    private function activation(string $activationId, array $ruleset, string $rulesetVersionId, ?string $previousId): RulesetActivation
    {
        return new RulesetActivation(
            $activationId,
            GameEdition::from($this->string($ruleset, 'game_edition')),
            $this->string($ruleset, 'patch'),
            $this->nullableString($ruleset, 'league'),
            $this->string($ruleset, 'parser_version'),
            $rulesetVersionId,
            $previousId,
        );
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException("Expected string database field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        return $this->string($data, $key);
    }

    private function property(object $row, string $key): string
    {
        return $this->string(get_object_vars($row), $key);
    }
}
