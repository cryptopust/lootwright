<?php

namespace App\Modules\Analysis\Persistence;

use App\Modules\Analysis\Events\AnalysisStateChanged;
use App\Modules\Analysis\Events\BuildArtifactParsed;
use App\Modules\Analysis\Events\BuildArtifactSubmitted;
use App\Modules\Analysis\Events\UserDataDeleted;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisProvenanceStatus;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\BuildDeletionResult;
use Lootwright\Application\Workflow\DTO\DeletionResult;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Application\Workflow\DTO\PortableAnalysisDocument;
use Lootwright\Application\Workflow\DTO\SubmissionReceipt;
use Lootwright\Application\Workflow\Exception\IdempotencyConflict;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\AnalysisDocumentRepository;
use Lootwright\Application\Workflow\Ports\BuildLifecycleRepository;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\TradePlanning\ManualTradeRecipe as DomainManualTradeRecipe;
use RuntimeException;

final class PostgresWorkflowRepository implements AnalysisDocumentRepository, BuildLifecycleRepository, WorkflowRepository
{
    public function submit(
        string $artifactId,
        string $analysisId,
        string $ownerId,
        string $idempotencyKey,
        GameEdition $edition,
        string $locale,
        string $artifactType,
        string $blobKey,
        string $artifactHashSha256,
        int $artifactBytes,
        string $parametersSnapshot,
        string $parametersHashSha256,
    ): SubmissionReceipt {
        $ownerHash = $this->ownerHash($ownerId);
        $idempotencyHash = $this->keyedHash('analysis-idempotency', $ownerHash."\0".$idempotencyKey);
        $existing = DB::table('build_artifacts')->where('idempotency_key_hash', $idempotencyHash)->first();

        if ($existing !== null) {
            return $this->replay($existing, $ownerHash, $artifactHashSha256, $parametersHashSha256, $edition);
        }

        $inserted = DB::table('build_artifacts')->insertOrIgnore([
            'id' => $artifactId,
            'owner_id_hash' => $ownerHash,
            'idempotency_key_hash' => $idempotencyHash,
            'game_edition' => $edition->value,
            'locale' => $locale,
            'artifact_type' => $artifactType,
            'blob_key' => $blobKey,
            'artifact_hash_sha256' => $artifactHashSha256,
            'artifact_bytes' => $artifactBytes,
            'state' => AnalysisState::Queued->value,
            'raw_expires_at' => CarbonImmutable::now('UTC')->addMinutes(
                max(1, (int) config('analysis-workflow.raw_artifact_ttl_minutes', 60)),
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            $concurrent = DB::table('build_artifacts')->where('idempotency_key_hash', $idempotencyHash)->first();

            if ($concurrent === null) {
                throw new RuntimeException('A concurrent submission could not be resolved.');
            }

            return $this->replay($concurrent, $ownerHash, $artifactHashSha256, $parametersHashSha256, $edition);
        }

        DB::table('analyses')->insert([
            'id' => $analysisId,
            'artifact_id' => $artifactId,
            'owner_id_hash' => $ownerHash,
            'game_edition' => $edition->value,
            'version' => 1,
            'state' => AnalysisState::Queued->value,
            'parameters_snapshot_encrypted' => Crypt::encryptString($parametersSnapshot),
            'parameters_hash_sha256' => $parametersHashSha256,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('build_import_attempts')->insert([
            'id' => (string) Str::uuid7(),
            'artifact_id' => $artifactId,
            'owner_id_hash' => $ownerHash,
            'game_edition' => $edition->value,
            'artifact_type' => $artifactType,
            'input_hash_sha256' => $artifactHashSha256,
            'input_bytes' => $artifactBytes,
            'state' => AnalysisState::Queued->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        event(new BuildArtifactSubmitted($artifactId, $analysisId, $edition->value));

        return new SubmissionReceipt($artifactId, $analysisId, AnalysisState::Queued, false);
    }

    public function claimArtifact(string $artifactId): ?ArtifactRecord
    {
        return DB::transaction(function () use ($artifactId): ?ArtifactRecord {
            $row = DB::table('build_artifacts')->where('id', $artifactId)->lockForUpdate()->first();

            if ($row === null || $this->string($row, 'state') !== AnalysisState::Queued->value) {
                return null;
            }

            DB::table('build_artifacts')->where('id', $artifactId)->update([
                'state' => AnalysisState::Processing->value,
                'processing_attempts' => DB::raw('processing_attempts + 1'),
                'updated_at' => now(),
            ]);
            DB::table('analyses')
                ->where('artifact_id', $artifactId)
                ->where('version', 1)
                ->where('state', AnalysisState::Queued->value)
                ->update(['state' => AnalysisState::Processing->value, 'updated_at' => now()]);
            DB::table('build_import_attempts')->where('artifact_id', $artifactId)->update([
                'state' => AnalysisState::Processing->value,
                'attempt_count' => DB::raw('attempt_count + 1'),
                'started_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->artifactFromRow($row, AnalysisState::Processing);
        }, 3);
    }

    public function artifact(string $artifactId): ?ArtifactRecord
    {
        $row = DB::table('build_artifacts')->where('id', $artifactId)->first();

        return $row === null ? null : $this->artifactFromRow($row);
    }

    public function saveParsedArtifact(string $artifactId, ParsedArtifact $parsed): void
    {
        DB::transaction(function () use ($artifactId, $parsed): void {
            $retentionUntil = CarbonImmutable::now('UTC')->addDays(
                max(1, (int) config('security.retention.analysis_days', 30)),
            );
            $updated = DB::table('build_artifacts')
                ->where('id', $artifactId)
                ->where('state', AnalysisState::Processing->value)
                ->whereNull('normalized_snapshot_encrypted')
                ->update([
                    'state' => $parsed->clarifications === []
                        ? AnalysisState::Completed->value
                        : AnalysisState::ClarificationRequired->value,
                    'adapter_key' => $parsed->adapterKey,
                    'parser_version' => $parsed->parserVersion,
                    'normalized_hash_sha256' => $parsed->normalizedHashSha256,
                    'patch_version' => $parsed->patchVersion,
                    'league' => $parsed->league,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new TerminalWorkflowFailure('artifact_state_conflict', 'The parsed artifact could not be committed from processing state.');
            }

            $analysis = DB::table('analyses')->where('artifact_id', $artifactId)->where('version', 1)->first();
            if ($analysis === null) {
                throw new TerminalWorkflowFailure('analysis_missing', 'The submitted artifact has no initial analysis.');
            }

            $snapshotId = (string) Str::uuid7();
            DB::table('normalized_build_snapshots')->insert([
                'id' => $snapshotId,
                'artifact_id' => $artifactId,
                'game_edition' => $parsed->edition->value,
                'adapter_key' => $parsed->adapterKey,
                'parser_version' => $parsed->parserVersion,
                'payload_encrypted' => Crypt::encryptString($parsed->normalizedSnapshot),
                'payload_hash_sha256' => $parsed->normalizedHashSha256,
                'patch_version' => $parsed->patchVersion,
                'league' => $parsed->league,
                'retention_until' => $retentionUntil,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $parameters = json_decode(Crypt::decryptString($this->string($analysis, 'parameters_snapshot_encrypted')), true);
            $selection = is_array($parameters) && is_array($parameters['selection'] ?? null) ? $parameters['selection'] : [];
            DB::table('builds')->insert([
                'id' => $artifactId,
                'artifact_id' => $artifactId,
                'normalized_snapshot_id' => $snapshotId,
                'owner_id_hash' => $this->string($analysis, 'owner_id_hash'),
                'game_edition' => $parsed->edition->value,
                'platform_realm' => $this->arrayString($selection, 'platform_realm'),
                'league' => $this->arrayString($selection, 'league') ?? $parsed->league,
                'content_goal' => $this->arrayString($selection, 'content_goal'),
                'selected_ruleset_id' => $this->arrayString($selection, 'ruleset_id'),
                'selected_ruleset_version' => $this->arrayString($selection, 'ruleset_version'),
                'selected_ruleset_checksum_sha256' => $this->arrayString($selection, 'ruleset_checksum_sha256'),
                'deletion_status' => 'active',
                'retention_until' => $retentionUntil,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('analyses')->where('artifact_id', $artifactId)->where('version', 1)->update([
                'build_id' => $artifactId,
                'adapter_key' => $parsed->adapterKey,
                'parser_version' => $parsed->parserVersion,
                'updated_at' => now(),
            ]);
            DB::table('build_import_attempts')->where('artifact_id', $artifactId)->update([
                'state' => $parsed->clarifications === [] ? AnalysisState::Completed->value : AnalysisState::ClarificationRequired->value,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
        event(new BuildArtifactParsed($artifactId, $parsed->edition->value, $parsed->adapterKey, $parsed->parserVersion));
    }

    public function claimAnalysis(string $analysisId): ?AnalysisRecord
    {
        return DB::transaction(function () use ($analysisId): ?AnalysisRecord {
            $row = DB::table('analyses')->where('id', $analysisId)->lockForUpdate()->first();

            if ($row === null || $this->string($row, 'state') !== AnalysisState::Queued->value) {
                return null;
            }

            DB::table('analyses')->where('id', $analysisId)->update([
                'state' => AnalysisState::Processing->value,
                'processing_attempts' => DB::raw('processing_attempts + 1'),
                'updated_at' => now(),
            ]);

            return $this->analysisFromRow($row, AnalysisState::Processing);
        }, 3);
    }

    public function analysisForOwner(string $analysisId, string $ownerId): ?AnalysisRecord
    {
        $row = DB::table('analyses')
            ->where('id', $analysisId)
            ->where('owner_id_hash', $this->ownerHash($ownerId))
            ->first();

        return $row === null ? null : $this->analysisFromRow($row);
    }

    public function analysis(string $analysisId): ?AnalysisRecord
    {
        $row = DB::table('analyses')->where('id', $analysisId)->first();

        return $row === null ? null : $this->analysisFromRow($row);
    }

    public function completeAnalysis(string $analysisId, DeterministicAnalysisSnapshot $snapshot): void
    {
        DB::transaction(function () use ($analysisId, $snapshot): void {
            $row = DB::table('analyses')->where('id', $analysisId)->lockForUpdate()->first();
            if ($row === null || $this->string($row, 'state') !== AnalysisState::Processing->value) {
                throw new TerminalWorkflowFailure('analysis_state_conflict', 'The immutable analysis output could not be committed.');
            }

            $lockVersion = $this->integer($row, 'lock_version');
            $updated = DB::table('analyses')
                ->where('id', $analysisId)
                ->where('state', AnalysisState::Processing->value)
                ->where('lock_version', $lockVersion)
                ->whereNull('output_snapshot_encrypted')
                ->update([
                    'state' => AnalysisState::Completed->value,
                    'adapter_key' => $snapshot->adapterKey,
                    'parser_version' => $snapshot->parserVersion,
                    'ruleset_id' => $snapshot->rulesetId,
                    'ruleset_version' => $snapshot->rulesetVersion,
                    'ruleset_checksum_sha256' => $snapshot->rulesetChecksumSha256,
                    'input_snapshot_encrypted' => Crypt::encryptString($snapshot->inputSnapshot),
                    'input_hash_sha256' => $snapshot->inputHashSha256,
                    'output_snapshot_encrypted' => Crypt::encryptString($snapshot->outputSnapshot),
                    'output_hash_sha256' => $snapshot->outputHashSha256,
                    'failure_code' => null,
                    'lock_version' => $lockVersion + 1,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new TerminalWorkflowFailure('analysis_state_conflict', 'Optimistic concurrency rejected a duplicate analysis completion.');
            }

            $this->persistProducts($analysisId, $snapshot);

            if ($snapshot->sourceId !== null && $snapshot->sourceVersion !== null) {
                DB::table('analysis_provenance_references')->insert([
                    'analysis_id' => $analysisId,
                    'source_id' => $snapshot->sourceId,
                    'source_version' => $snapshot->sourceVersion,
                    'ruleset_id' => $snapshot->rulesetId,
                    'ruleset_version' => $snapshot->rulesetVersion,
                    'ruleset_checksum_sha256' => $snapshot->rulesetChecksumSha256,
                    'created_at' => now(),
                ]);
            }
        }, 3);

        event(new AnalysisStateChanged($analysisId, AnalysisState::Completed->value));
    }

    public function transitionAnalysis(
        string $analysisId,
        AnalysisState $state,
        ?string $detailSnapshot = null,
        ?string $failureCode = null,
    ): void {
        $query = DB::table('analyses')->where('id', $analysisId);

        if ($state === AnalysisState::Queued) {
            $query->whereIn('state', [AnalysisState::Processing->value, AnalysisState::ClarificationRequired->value]);
        } else {
            $query->whereNotIn('state', [AnalysisState::Completed->value, AnalysisState::PolicyBlocked->value]);
        }

        $values = [
            'state' => $state->value,
            'failure_code' => $failureCode,
            'updated_at' => now(),
        ];

        if ($detailSnapshot !== null) {
            $values['clarification_snapshot_encrypted'] = Crypt::encryptString($detailSnapshot);
        }

        if ($query->update($values) === 1) {
            event(new AnalysisStateChanged($analysisId, $state->value));
        }
    }

    public function requeueArtifact(string $artifactId): void
    {
        DB::table('build_artifacts')->where('id', $artifactId)->where('state', AnalysisState::Processing->value)->update([
            'state' => AnalysisState::Queued->value,
            'updated_at' => now(),
        ]);
        DB::table('analyses')->where('artifact_id', $artifactId)->where('version', 1)->where('state', AnalysisState::Processing->value)->update([
            'state' => AnalysisState::Queued->value,
            'updated_at' => now(),
        ]);
    }

    public function requeueAnalysis(string $analysisId): void
    {
        DB::table('analyses')->where('id', $analysisId)->where('state', AnalysisState::Processing->value)->update([
            'state' => AnalysisState::Queued->value,
            'updated_at' => now(),
        ]);
    }

    public function failArtifact(string $artifactId, AnalysisState $state, string $failureCode): void
    {
        DB::transaction(function () use ($artifactId, $state, $failureCode): void {
            DB::table('build_artifacts')->where('id', $artifactId)->whereNotIn('state', [AnalysisState::Completed->value, AnalysisState::PolicyBlocked->value])->update([
                'state' => $state->value,
                'failure_code' => $failureCode,
                'updated_at' => now(),
            ]);
            $analysisIds = DB::table('analyses')->where('artifact_id', $artifactId)->pluck('id');
            DB::table('analyses')->where('artifact_id', $artifactId)->whereNotIn('state', [AnalysisState::Completed->value, AnalysisState::PolicyBlocked->value])->update([
                'state' => $state->value,
                'failure_code' => $failureCode,
                'updated_at' => now(),
            ]);
            DB::table('build_import_attempts')->where('artifact_id', $artifactId)->update([
                'state' => $state->value,
                'failure_code' => $failureCode,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($analysisIds as $analysisId) {
                if (is_string($analysisId)) {
                    event(new AnalysisStateChanged($analysisId, $state->value));
                }
            }
        }, 3);
    }

    public function markArtifactBlobDeleted(string $artifactId): void
    {
        DB::table('build_artifacts')->where('id', $artifactId)->whereNull('raw_deleted_at')->update([
            'raw_deleted_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function expiredArtifacts(): array
    {
        $artifacts = [];

        foreach (DB::table('build_artifacts')
            ->whereNull('raw_deleted_at')
            ->where('raw_expires_at', '<=', now())
            ->orderBy('raw_expires_at')
            ->limit(500)
            ->get() as $row) {
            $artifacts[] = $this->artifactFromRow($row);
        }

        return $artifacts;
    }

    public function expireArtifact(string $artifactId): void
    {
        DB::transaction(function () use ($artifactId): void {
            DB::table('build_artifacts')
                ->where('id', $artifactId)
                ->whereNull('raw_deleted_at')
                ->update([
                    'state' => AnalysisState::Failed->value,
                    'failure_code' => 'raw_artifact_expired',
                    'raw_deleted_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::table('analyses')
                ->where('artifact_id', $artifactId)
                ->whereIn('state', [AnalysisState::Queued->value, AnalysisState::Processing->value])
                ->update([
                    'state' => AnalysisState::Failed->value,
                    'failure_code' => 'raw_artifact_expired',
                    'updated_at' => now(),
                ]);
        }, 3);
    }

    public function createAnalysisVersion(
        string $analysisId,
        AnalysisRecord $parent,
        string $parametersSnapshot,
        string $parametersHashSha256,
    ): AnalysisRecord {
        return DB::transaction(function () use ($analysisId, $parent, $parametersSnapshot, $parametersHashSha256): AnalysisRecord {
            $artifact = DB::table('build_artifacts')->where('id', $parent->artifactId)->lockForUpdate()->first();

            if ($artifact === null || ! DB::table('normalized_build_snapshots')->where('artifact_id', $parent->artifactId)->exists()) {
                throw new TerminalWorkflowFailure('normalized_artifact_missing', 'A parsed artifact is required for reanalysis.');
            }

            $version = ((int) DB::table('analyses')->where('artifact_id', $parent->artifactId)->max('version')) + 1;
            DB::table('analyses')->insert([
                'id' => $analysisId,
                'artifact_id' => $parent->artifactId,
                'build_id' => $parent->artifactId,
                'owner_id_hash' => $this->string($artifact, 'owner_id_hash'),
                'parent_analysis_id' => $parent->id,
                'game_edition' => $parent->edition->value,
                'version' => $version,
                'state' => AnalysisState::Queued->value,
                'parameters_snapshot_encrypted' => Crypt::encryptString($parametersSnapshot),
                'parameters_hash_sha256' => $parametersHashSha256,
                'adapter_key' => $this->nullableString($artifact, 'adapter_key'),
                'parser_version' => $this->nullableString($artifact, 'parser_version'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('analyses')->where('id', $analysisId)->first();

            if ($row === null) {
                throw new RuntimeException('The analysis version could not be read after creation.');
            }

            event(new AnalysisStateChanged($analysisId, AnalysisState::Queued->value));

            return $this->analysisFromRow($row);
        }, 3);
    }

    public function ownerArtifactBlobKeys(string $ownerId): array
    {
        $blobKeys = [];

        foreach (DB::table('build_artifacts')->where('owner_id_hash', $this->ownerHash($ownerId))->pluck('blob_key') as $blobKey) {
            if (is_string($blobKey)) {
                $blobKeys[] = $blobKey;
            }
        }

        return $blobKeys;
    }

    public function deleteOwnerData(string $ownerId): DeletionResult
    {
        $ownerHash = $this->ownerHash($ownerId);
        $blobKeys = [];

        foreach (DB::table('build_artifacts')->where('owner_id_hash', $ownerHash)->pluck('blob_key') as $blobKey) {
            if (is_string($blobKey)) {
                $blobKeys[] = $blobKey;
            }
        }
        $artifactIds = DB::table('build_artifacts')->where('owner_id_hash', $ownerHash)->pluck('id');
        $analyses = DB::table('analyses')->where('owner_id_hash', $ownerHash)->count();
        $artifacts = $artifactIds->count();

        DB::table('build_artifacts')->where('owner_id_hash', $ownerHash)->delete();
        DB::table('user_data_deletion_records')->insert([
            'id' => (string) Str::uuid7(),
            'artifacts_deleted' => $artifacts,
            'analyses_deleted' => $analyses,
            'reason' => 'user_requested',
            'requested_at' => now(),
        ]);
        event(new UserDataDeleted($artifacts, $analyses));

        return new DeletionResult($artifacts, $analyses, $blobKeys);
    }

    public function portableForOwner(string $analysisId, string $ownerId): ?PortableAnalysisDocument
    {
        $analysis = DB::table('analyses')
            ->where('id', $analysisId)
            ->where('owner_id_hash', $this->ownerHash($ownerId))
            ->where('state', AnalysisState::Completed->value)
            ->first();

        if ($analysis === null) {
            return null;
        }

        $build = DB::table('builds')->where('id', $this->nullableString($analysis, 'build_id'))->first();
        $artifact = DB::table('build_artifacts')->where('id', $this->string($analysis, 'artifact_id'))->first();
        $snapshot = DB::table('normalized_build_snapshots')->where('artifact_id', $this->string($analysis, 'artifact_id'))->first();

        if ($build === null || $artifact === null || $snapshot === null) {
            return null;
        }

        $parameters = $this->json(Crypt::decryptString($this->string($analysis, 'parameters_snapshot_encrypted')));
        $provenance = $this->rows('analysis_provenance_references', $analysisId, [
            'source_id', 'source_version', 'ruleset_id', 'ruleset_version', 'ruleset_checksum_sha256',
        ]);
        $policy = $this->rows('analysis_policy_decisions', $analysisId, [
            'source_id', 'source_version', 'capability', 'operation', 'decision', 'reason', 'policy_version', 'evidence_ids',
        ], ['evidence_ids']);
        $explanationRow = DB::table('analysis_explanations')->where('analysis_id', $analysisId)->first();

        return new PortableAnalysisDocument(
            '1.0.0',
            $analysisId,
            $this->integer($analysis, 'version'),
            $this->string($analysis, 'game_edition'),
            [
                'build_id' => $this->string($build, 'id'),
                'artifact_hash_sha256' => $this->string($artifact, 'artifact_hash_sha256'),
                'normalized_hash_sha256' => $this->string($snapshot, 'payload_hash_sha256'),
                'adapter' => $this->string($snapshot, 'adapter_key'),
                'parser_version' => $this->string($snapshot, 'parser_version'),
                'patch' => $this->nullableString($snapshot, 'patch_version'),
                'league' => $this->nullableString($build, 'league'),
            ],
            is_array($parameters['selection'] ?? null) ? $parameters['selection'] : [],
            [
                'id' => $this->string($analysis, 'ruleset_id'),
                'version' => $this->string($analysis, 'ruleset_version'),
                'checksum_sha256' => $this->string($analysis, 'ruleset_checksum_sha256'),
            ],
            $this->json($this->verifiedAnalysisSnapshot($analysis, 'input')),
            $this->json($this->verifiedAnalysisSnapshot($analysis, 'output')),
            $provenance,
            $policy,
            $this->payloadRows('analysis_findings', $analysisId),
            $this->payloadRows('analysis_recommendations', $analysisId),
            $this->payloadRows('manual_trade_recipes', $analysisId),
            $explanationRow === null ? null : $this->json($this->verifiedEncryptedPayload($explanationRow, 'explanation')),
            $this->string($analysis, 'input_hash_sha256'),
            $this->string($analysis, 'output_hash_sha256'),
        );
    }

    public function provenanceForOwner(string $analysisId, string $ownerId): ?AnalysisProvenanceStatus
    {
        $exists = DB::table('analyses')
            ->where('id', $analysisId)
            ->where('owner_id_hash', $this->ownerHash($ownerId))
            ->exists();

        if (! $exists) {
            return null;
        }

        return new AnalysisProvenanceStatus(
            $analysisId,
            $this->rows('analysis_provenance_references', $analysisId, [
                'source_id', 'source_version', 'ruleset_id', 'ruleset_version', 'ruleset_checksum_sha256',
            ]),
            $this->rows('analysis_policy_decisions', $analysisId, [
                'source_id', 'source_version', 'capability', 'operation', 'decision', 'reason', 'policy_version', 'evidence_ids',
            ], ['evidence_ids']),
        );
    }

    public function blobKeyForOwner(string $buildId, string $ownerId): ?string
    {
        $value = DB::table('builds as builds')
            ->join('build_artifacts as artifacts', 'artifacts.id', '=', 'builds.artifact_id')
            ->where('builds.id', $buildId)
            ->where('builds.owner_id_hash', $this->ownerHash($ownerId))
            ->value('artifacts.blob_key');

        return is_string($value) ? $value : null;
    }

    public function deleteBuildForOwner(string $buildId, string $ownerId): ?BuildDeletionResult
    {
        $build = DB::table('builds')->where('id', $buildId)->where('owner_id_hash', $this->ownerHash($ownerId))->lockForUpdate()->first();

        if ($build === null) {
            return null;
        }

        $artifactId = $this->string($build, 'artifact_id');
        $blobKey = DB::table('build_artifacts')->where('id', $artifactId)->value('blob_key');
        $analyses = DB::table('analyses')->where('artifact_id', $artifactId)->count();
        DB::table('builds')->where('id', $buildId)->update(['deletion_status' => 'deletion_requested', 'updated_at' => now()]);
        DB::table('build_artifacts')->where('id', $artifactId)->delete();
        DB::table('user_data_deletion_records')->insert([
            'id' => (string) Str::uuid7(),
            'artifacts_deleted' => 1,
            'analyses_deleted' => $analyses,
            'reason' => 'build_deleted',
            'requested_at' => now(),
        ]);

        return new BuildDeletionResult($buildId, $analyses, is_string($blobKey) ? $blobKey : null);
    }

    private function persistProducts(string $analysisId, DeterministicAnalysisSnapshot $snapshot): void
    {
        foreach ($snapshot->findings as $sequence => $finding) {
            $payload = CanonicalJson::encode($finding);
            DB::table('analysis_findings')->insert([
                'id' => (string) Str::uuid7(),
                'analysis_id' => $analysisId,
                'sequence' => $sequence,
                'code' => $finding->code,
                'severity' => $finding->severity->value,
                'payload_encrypted' => Crypt::encryptString($payload),
                'payload_hash_sha256' => hash('sha256', $payload),
                'created_at' => now(),
            ]);
        }

        foreach ($snapshot->recommendations as $sequence => $recommendation) {
            $payload = CanonicalJson::encode($recommendation);
            DB::table('analysis_recommendations')->insert([
                'id' => (string) Str::uuid7(),
                'analysis_id' => $analysisId,
                'sequence' => $sequence,
                'code' => $recommendation->code,
                'priority' => $recommendation->priority->value,
                'payload_encrypted' => Crypt::encryptString($payload),
                'payload_hash_sha256' => hash('sha256', $payload),
                'created_at' => now(),
            ]);
        }

        foreach ($snapshot->recipes as $sequence => $recipe) {
            $payload = CanonicalJson::encode($recipe);
            $key = $recipe instanceof DomainManualTradeRecipe ? $recipe->recommendationCode : $recipe->slot;
            DB::table('manual_trade_recipes')->insert([
                'id' => (string) Str::uuid7(),
                'analysis_id' => $analysisId,
                'sequence' => $sequence,
                'recipe_key' => $key,
                'payload_encrypted' => Crypt::encryptString($payload),
                'payload_hash_sha256' => hash('sha256', $payload),
                'created_at' => now(),
            ]);
        }
    }

    /** @param list<string> $columns
     * @param  list<string>  $jsonColumns
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, string $analysisId, array $columns, array $jsonColumns = []): array
    {
        $rows = [];

        foreach (DB::table($table)->where('analysis_id', $analysisId)->orderBy('id')->get($columns) as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;
                $values[$column] = in_array($column, $jsonColumns, true) && is_string($value)
                    ? json_decode($value, true, flags: JSON_THROW_ON_ERROR)
                    : $value;
            }
            $rows[] = $values;
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function payloadRows(string $table, string $analysisId): array
    {
        $payloads = [];

        foreach (DB::table($table)->where('analysis_id', $analysisId)->orderBy('sequence')->get() as $row) {
            $payload = Crypt::decryptString($this->string($row, 'payload_encrypted'));
            if (! hash_equals($this->string($row, 'payload_hash_sha256'), hash('sha256', $payload))) {
                throw new TerminalWorkflowFailure('persisted_product_hash_mismatch', 'A persisted deterministic product failed checksum verification.');
            }
            $payloads[] = $this->json($payload);
        }

        return $payloads;
    }

    private function verifiedAnalysisSnapshot(object $analysis, string $kind): string
    {
        $snapshot = Crypt::decryptString($this->string($analysis, $kind.'_snapshot_encrypted'));
        $hash = $this->string($analysis, $kind.'_hash_sha256');

        if (! hash_equals($hash, hash('sha256', $snapshot))) {
            throw new TerminalWorkflowFailure(
                'persisted_'.$kind.'_hash_mismatch',
                'A persisted deterministic analysis snapshot failed checksum verification.',
            );
        }

        return $snapshot;
    }

    private function verifiedEncryptedPayload(object $row, string $kind): string
    {
        $payload = Crypt::decryptString($this->string($row, 'payload_encrypted'));

        if (! hash_equals($this->string($row, 'payload_hash_sha256'), hash('sha256', $payload))) {
            throw new TerminalWorkflowFailure(
                'persisted_'.$kind.'_hash_mismatch',
                'A persisted encrypted payload failed checksum verification.',
            );
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function json(string $value): array
    {
        $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Expected a JSON object in workflow persistence.');
        }

        return $decoded;
    }

    /** @param array<array-key, mixed> $values */
    private function arrayString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    private function replay(
        object $artifact,
        string $ownerHash,
        string $artifactHash,
        string $parametersHash,
        GameEdition $edition,
    ): SubmissionReceipt {
        $analysis = DB::table('analyses')->where('artifact_id', $this->string($artifact, 'id'))->where('version', 1)->first();

        if ($analysis === null
            || $this->string($artifact, 'owner_id_hash') !== $ownerHash
            || $this->string($artifact, 'artifact_hash_sha256') !== $artifactHash
            || $this->string($artifact, 'game_edition') !== $edition->value
            || $this->string($analysis, 'parameters_hash_sha256') !== $parametersHash
        ) {
            throw new IdempotencyConflict('The idempotency key was already used for a different submission.');
        }

        return new SubmissionReceipt(
            $this->string($artifact, 'id'),
            $this->string($analysis, 'id'),
            AnalysisState::from($this->string($analysis, 'state')),
            true,
        );
    }

    private function artifactFromRow(object $row, ?AnalysisState $state = null): ArtifactRecord
    {
        $analysisId = DB::table('analyses')->where('artifact_id', $this->string($row, 'id'))->orderBy('version')->value('id');

        if (! is_string($analysisId)) {
            throw new RuntimeException('The artifact has no analysis.');
        }

        $encrypted = $this->nullableString($row, 'normalized_snapshot_encrypted');

        if ($encrypted === null) {
            $snapshot = DB::table('normalized_build_snapshots')->where('artifact_id', $this->string($row, 'id'))->first();
            $encrypted = $snapshot === null ? null : $this->nullableString($snapshot, 'payload_encrypted');
        }

        return new ArtifactRecord(
            $this->string($row, 'id'),
            $this->string($row, 'owner_id_hash'),
            $analysisId,
            GameEdition::from($this->string($row, 'game_edition')),
            $this->string($row, 'artifact_type'),
            $this->string($row, 'blob_key'),
            $this->string($row, 'artifact_hash_sha256'),
            $state ?? AnalysisState::from($this->string($row, 'state')),
            $this->nullableString($row, 'adapter_key'),
            $this->nullableString($row, 'parser_version'),
            $encrypted === null ? null : Crypt::decryptString($encrypted),
            $this->nullableString($row, 'normalized_hash_sha256'),
            $this->nullableString($row, 'patch_version'),
            $this->nullableString($row, 'league'),
        );
    }

    private function analysisFromRow(object $row, ?AnalysisState $state = null): AnalysisRecord
    {
        return new AnalysisRecord(
            $this->string($row, 'id'),
            $this->string($row, 'artifact_id'),
            $this->string($row, 'owner_id_hash'),
            GameEdition::from($this->string($row, 'game_edition')),
            $this->integer($row, 'version'),
            $state ?? AnalysisState::from($this->string($row, 'state')),
            Crypt::decryptString($this->string($row, 'parameters_snapshot_encrypted')),
            $this->string($row, 'parameters_hash_sha256'),
            $this->nullableString($row, 'parent_analysis_id'),
            $this->nullableString($row, 'adapter_key'),
            $this->nullableString($row, 'parser_version'),
            $this->nullableString($row, 'ruleset_id'),
            $this->nullableString($row, 'ruleset_version'),
            $this->nullableString($row, 'ruleset_checksum_sha256'),
            $this->decryptNullable($row, 'input_snapshot_encrypted'),
            $this->nullableString($row, 'input_hash_sha256'),
            $this->decryptNullable($row, 'output_snapshot_encrypted'),
            $this->nullableString($row, 'output_hash_sha256'),
            $this->decryptNullable($row, 'clarification_snapshot_encrypted'),
            $this->nullableString($row, 'failure_code'),
        );
    }

    private function decryptNullable(object $row, string $key): ?string
    {
        $value = $this->nullableString($row, $key);

        return $value === null ? null : Crypt::decryptString($value);
    }

    private function ownerHash(string $ownerId): string
    {
        return $this->keyedHash('analysis-owner', $ownerId);
    }

    private function keyedHash(string $purpose, string $value): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('The application encryption key is not configured.');
        }

        return hash_hmac('sha256', $purpose."\0".$value, $key);
    }

    private function string(object $row, string $key): string
    {
        $value = $row->{$key} ?? null;

        if (! is_string($value)) {
            throw new RuntimeException("Expected string workflow field {$key}.");
        }

        return $value;
    }

    private function nullableString(object $row, string $key): ?string
    {
        $value = $row->{$key} ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new RuntimeException("Expected nullable string workflow field {$key}.");
        }

        return $value;
    }

    private function integer(object $row, string $key): int
    {
        $value = $row->{$key} ?? null;

        if (! is_int($value)) {
            throw new RuntimeException("Expected integer workflow field {$key}.");
        }

        return $value;
    }
}
