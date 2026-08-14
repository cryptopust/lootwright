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
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeletionResult;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Application\Workflow\DTO\SubmissionReceipt;
use Lootwright\Application\Workflow\Exception\IdempotencyConflict;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Domain\Shared\Game\GameEdition;
use RuntimeException;

final class PostgresWorkflowRepository implements WorkflowRepository
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
                'normalized_snapshot_encrypted' => Crypt::encryptString($parsed->normalizedSnapshot),
                'normalized_hash_sha256' => $parsed->normalizedHashSha256,
                'patch_version' => $parsed->patchVersion,
                'league' => $parsed->league,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new TerminalWorkflowFailure('artifact_state_conflict', 'The parsed artifact could not be committed from processing state.');
        }

        DB::table('analyses')->where('artifact_id', $artifactId)->where('version', 1)->update([
            'adapter_key' => $parsed->adapterKey,
            'parser_version' => $parsed->parserVersion,
            'updated_at' => now(),
        ]);
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
        $updated = DB::table('analyses')
            ->where('id', $analysisId)
            ->where('state', AnalysisState::Processing->value)
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
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new TerminalWorkflowFailure('analysis_state_conflict', 'The immutable analysis output could not be committed.');
        }

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

            if ($artifact === null || $this->nullableString($artifact, 'normalized_snapshot_encrypted') === null) {
                throw new TerminalWorkflowFailure('normalized_artifact_missing', 'A parsed artifact is required for reanalysis.');
            }

            $version = ((int) DB::table('analyses')->where('artifact_id', $parent->artifactId)->max('version')) + 1;
            DB::table('analyses')->insert([
                'id' => $analysisId,
                'artifact_id' => $parent->artifactId,
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
