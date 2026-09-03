<?php

namespace App\Modules\ExternalSources;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\ExternalSources\DTO\SourceImportReport;
use Lootwright\Application\ExternalSources\DTO\StagedSourceRecord;
use Lootwright\Application\ExternalSources\Ports\SourceImportStaging;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class DatabaseSourceImportStaging implements SourceImportStaging
{
    public function __construct(private SourceGovernancePolicy $policy) {}

    public function stage(
        string $sourceCode,
        string $sourceVersion,
        string $operation,
        GameEdition $edition,
        string $sourceChecksumSha256,
        string $normalizedChecksumSha256,
        ?string $rulesetTarget,
        array $records,
        array $policyConditions = [],
    ): SourceImportReport {
        if (! $this->policy->permitsImport($sourceCode, $sourceVersion, $operation, $policyConditions)) {
            throw new DomainException('The Policy and Provenance Gate denied source staging.');
        }
        if (preg_match('/^[0-9a-f]{64}$/D', $sourceChecksumSha256) !== 1
            || preg_match('/^[0-9a-f]{64}$/D', $normalizedChecksumSha256) !== 1
            || count($records) > 50_000
        ) {
            throw new DomainException('The source staging request exceeds its integrity limits.');
        }

        $importIdentity = hash('sha256', implode("\0", [$sourceCode, $sourceVersion, $edition->value, $sourceChecksumSha256, $normalizedChecksumSha256]));

        return DB::transaction(function (Connection $_connection) use ($sourceCode, $sourceVersion, $operation, $edition, $sourceChecksumSha256, $normalizedChecksumSha256, $rulesetTarget, $records, $importIdentity): SourceImportReport {
            $existing = DB::table('source_import_reports')
                ->where('import_identity_sha256', $importIdentity)
                ->first();

            if ($existing !== null) {
                return new SourceImportReport(
                    (string) $existing->id,
                    (string) $existing->import_run_id,
                    (string) $existing->status,
                    true,
                    $this->records((string) $existing->id),
                    is_string($existing->source_snapshot_id) ? $existing->source_snapshot_id : null,
                );
            }

            $now = CarbonImmutable::now('UTC');
            $versionId = DB::table('policy_data_source_versions')
                ->where('source_id', $sourceCode)->where('version', $sourceVersion)->value('id');

            if (! is_int($versionId)) {
                throw new DomainException('The source version is not registered.');
            }

            $runId = (string) Str::uuid7();
            $reportId = (string) Str::uuid7();
            DB::table('external_source_sync_runs')->insert([
                'id' => $runId,
                'policy_source_version_id' => $versionId,
                'source_key' => $sourceCode,
                'source_version' => $sourceVersion,
                'operation' => $operation,
                'game_edition' => $edition->value,
                'status' => 'staged',
                'response_checksum_sha256' => $sourceChecksumSha256,
                'started_at' => $now,
                'fetched_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $received = count($records);
            $rejected = count(array_filter($records, static fn (StagedSourceRecord $record): bool => $record->status === 'rejected'));
            DB::table('source_import_reports')->insert([
                'id' => $reportId,
                'import_run_id' => $runId,
                'source_code' => $sourceCode,
                'source_version' => $sourceVersion,
                'game_edition' => $edition->value,
                'ruleset_target' => $rulesetTarget,
                'status' => $rejected === $received && $received > 0 ? 'rejected' : 'staged',
                'policy_status' => 'allowed',
                'source_checksum_sha256' => $sourceChecksumSha256,
                'normalized_checksum_sha256' => $normalizedChecksumSha256,
                'import_identity_sha256' => $importIdentity,
                'records_received' => $received,
                'records_imported' => 0,
                'records_rejected' => $rejected,
                'summary' => CanonicalJson::encode(['staging_schema' => '1.0.0', 'raw_payload_stored' => false]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rows = [];
            foreach ($records as $record) {
                if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,191}$/D', $record->recordKey) !== 1
                    || preg_match('/^[0-9a-f]{64}$/D', $record->checksumSha256) !== 1
                    || ! in_array($record->status, ['staged', 'rejected'], true)
                ) {
                    throw new DomainException('A staged source record is invalid.');
                }
                $payload = $record->normalizedPayload === null ? null : CanonicalJson::encode($record->normalizedPayload);
                if ($payload !== null && strlen($payload) > 262_144) {
                    throw new DomainException('A staged source record exceeds 256 KiB.');
                }
                $rows[] = [
                    'id' => (string) Str::uuid7(),
                    'import_report_id' => $reportId,
                    'record_key' => $record->recordKey,
                    'checksum_sha256' => $record->checksumSha256,
                    'status' => $record->status,
                    'rejection_code' => $record->rejectionCode,
                    'normalized_payload' => $payload,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('source_import_staging_records')->insert($chunk);
            }
            if ($rejected === $received && $received > 0) {
                DB::table('source_import_reports')->where('id', $reportId)->update(['completed_at' => $now, 'updated_at' => $now]);
                DB::table('external_source_sync_runs')->where('id', $runId)->update([
                    'status' => 'quarantined',
                    'failure_code' => 'staging_records_rejected',
                    'completed_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return new SourceImportReport($reportId, $runId, $rejected === $received && $received > 0 ? 'rejected' : 'staged', false, $records);
        }, 3);
    }

    public function approve(string $reportId, string $snapshotId): void
    {
        DB::transaction(function () use ($reportId, $snapshotId): void {
            $report = DB::table('source_import_reports')->where('id', $reportId)->lockForUpdate()->first();
            if ($report === null || $report->status !== 'staged') {
                throw new DomainException('Only a staged import report can be approved.');
            }
            if (! $this->policy->permitsImport(
                (string) $report->source_code,
                (string) $report->source_version,
                'source.import.approve',
                ['approved_snapshot', 'same_source', 'same_edition', 'no_canonical_mutation'],
            )) {
                throw new DomainException('The Policy and Provenance Gate denied source approval.');
            }
            $snapshot = DB::table('source_snapshots')->where('id', $snapshotId)->first();
            if ($snapshot === null
                || $snapshot->source_code !== $report->source_code
                || $snapshot->game_edition !== $report->game_edition
                || $snapshot->status !== 'valid'
                || ! hash_equals((string) $report->normalized_checksum_sha256, (string) $snapshot->checksum_sha256)
                || ! hash_equals((string) $report->source_checksum_sha256, (string) $snapshot->upstream_checksum_sha256)
            ) {
                throw new DomainException('The approved immutable snapshot does not match the staged report.');
            }
            $now = CarbonImmutable::now('UTC');
            DB::table('source_import_staging_records')->where('import_report_id', $reportId)->where('status', 'staged')->update(['status' => 'approved', 'updated_at' => $now]);
            DB::table('source_import_reports')->where('id', $reportId)->update([
                'status' => 'approved',
                'records_imported' => DB::table('source_import_staging_records')->where('import_report_id', $reportId)->where('status', 'approved')->count(),
                'source_snapshot_id' => $snapshotId,
                'completed_at' => $now,
                'updated_at' => $now,
            ]);
        }, 3);
    }

    public function reject(string $reportId, string $failureCode): void
    {
        if (preg_match('/^[a-z][a-z0-9_]{1,95}$/D', $failureCode) !== 1) {
            throw new DomainException('The source rejection code is invalid.');
        }
        DB::transaction(function () use ($reportId, $failureCode): void {
            $report = DB::table('source_import_reports')->where('id', $reportId)->lockForUpdate()->first();
            if ($report === null || $report->status !== 'staged') {
                throw new DomainException('Only a staged import report can be rejected.');
            }
            $now = CarbonImmutable::now('UTC');
            DB::table('source_import_staging_records')->where('import_report_id', $reportId)->where('status', 'staged')->update(['status' => 'rejected', 'rejection_code' => $failureCode, 'updated_at' => $now]);
            DB::table('source_import_reports')->where('id', $reportId)->update([
                'status' => 'rejected',
                'records_rejected' => DB::table('source_import_staging_records')->where('import_report_id', $reportId)->where('status', 'rejected')->count(),
                'completed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('external_source_sync_runs')->where('id', $report->import_run_id)->update(['status' => 'failed', 'failure_code' => $failureCode, 'completed_at' => $now, 'updated_at' => $now]);
        }, 3);
    }

    public function rollback(string $reportId, string $actorType): string
    {
        if (! in_array($actorType, ['operator', 'admin'], true)) {
            throw new DomainException('The rollback actor type is invalid.');
        }

        return DB::transaction(function () use ($reportId, $actorType): string {
            $report = DB::table('source_import_reports')->where('id', $reportId)->lockForUpdate()->first();
            if ($report === null || ! in_array($report->status, ['staged', 'rejected'], true)) {
                throw new DomainException('Published or approved imports cannot be rolled back by mutation; activate a prior immutable ruleset instead.');
            }
            $conditions = ['authorized_actor', 'staging_only', 'no_canonical_mutation', $actorType.'_workflow'];
            if (! $this->policy->permitsImport(
                (string) $report->source_code,
                (string) $report->source_version,
                'source.import.rollback',
                $conditions,
            )) {
                throw new DomainException('The Policy and Provenance Gate denied source rollback.');
            }
            $now = CarbonImmutable::now('UTC');
            $rollbackId = (string) Str::uuid7();
            $runId = (string) Str::uuid7();
            DB::table('external_source_sync_runs')->insert([
                'id' => $runId, 'policy_source_version_id' => DB::table('external_source_sync_runs')->where('id', $report->import_run_id)->value('policy_source_version_id'),
                'source_key' => $report->source_code, 'source_version' => DB::table('external_source_sync_runs')->where('id', $report->import_run_id)->value('source_version'),
                'operation' => 'source.import.rollback', 'game_edition' => $report->game_edition, 'status' => 'succeeded',
                'response_checksum_sha256' => $report->source_checksum_sha256, 'started_at' => $now, 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('source_import_reports')->insert([
                'id' => $rollbackId, 'import_run_id' => $runId, 'source_code' => $report->source_code, 'game_edition' => $report->game_edition,
                'source_version' => $report->source_version,
                'ruleset_target' => $report->ruleset_target, 'status' => 'rolled_back', 'policy_status' => 'allowed',
                'source_checksum_sha256' => $report->source_checksum_sha256, 'normalized_checksum_sha256' => $report->normalized_checksum_sha256,
                'import_identity_sha256' => null,
                'records_received' => 0, 'records_imported' => 0, 'records_rejected' => 0,
                'summary' => CanonicalJson::encode(['actor_type' => $actorType, 'raw_payload_stored' => false]), 'rollback_of_report_id' => $reportId,
                'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('source_import_staging_records')->where('import_report_id', $reportId)->update(['status' => 'rolled_back', 'normalized_payload' => null, 'updated_at' => $now]);
            DB::table('source_import_reports')->where('id', $reportId)->update(['status' => 'rolled_back', 'completed_at' => $now, 'updated_at' => $now]);

            return $rollbackId;
        }, 3);
    }

    /** @return list<StagedSourceRecord> */
    private function records(string $reportId): array
    {
        return array_values(DB::table('source_import_staging_records')->where('import_report_id', $reportId)->orderBy('record_key')->get()->map(
            static fn (object $row): StagedSourceRecord => new StagedSourceRecord(
                (string) $row->record_key,
                (string) $row->checksum_sha256,
                (string) $row->status,
                is_string($row->rejection_code) ? $row->rejection_code : null,
                is_string($row->normalized_payload) ? json_decode($row->normalized_payload, true, flags: JSON_THROW_ON_ERROR) : null,
            ),
        )->all());
    }
}
