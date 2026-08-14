<?php

namespace App\Modules\BuildIntake;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\Workflow\Ports\SupplementalUserDataEraser;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final class PobImportStore implements SupplementalUserDataEraser
{
    public function store(
        PobImportResult $result,
        string $requestHash,
        int $retentionHours,
        string $idempotencyKey,
        string $ownerId,
    ): StoredPobImport {
        $maximum = max(1, (int) config('build-intake.maximum_retention_hours', 168));
        $retentionHours = max(1, min($retentionHours, $maximum));
        $ownerHash = $this->keyedHash('pob-import-owner', $ownerId);
        $idempotencyHash = $this->keyedHash('pob-import-idempotency', $ownerHash."\0".$idempotencyKey);
        $existing = DB::table('pob_imports')->where('idempotency_key_hash', $idempotencyHash)->first();

        if ($existing !== null) {
            if (CarbonImmutable::parse($existing->expires_at)->isFuture()) {
                return $this->replay($existing, $result, $requestHash, $idempotencyKey, $ownerHash);
            }

            DB::table('pob_imports')->where('id', $existing->id)->delete();
        }

        $id = (string) Str::uuid7();
        $deletionToken = $this->deletionToken($id, $idempotencyKey, $ownerHash);
        $expiresAt = CarbonImmutable::now('UTC')->addHours($retentionHours);

        try {
            DB::table('pob_imports')->insert([
                'id' => $id,
                'owner_id_hash' => $ownerHash,
                'idempotency_key_hash' => $idempotencyHash,
                'request_hash_sha256' => $requestHash,
                'input_checksum_sha256' => $result->inputChecksumSha256,
                'outcome' => 'normalized',
                'game_edition' => $result->canonicalBuild->edition->value,
                'parser_version' => $result->parserVersion,
                'normalized_payload_encrypted' => Crypt::encryptString(CanonicalJson::encode($result)),
                'deletion_token_hash_sha256' => hash('sha256', $deletionToken),
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            $concurrent = DB::table('pob_imports')->where('idempotency_key_hash', $idempotencyHash)->first();

            if ($concurrent === null) {
                throw $exception;
            }

            return $this->replay($concurrent, $result, $requestHash, $idempotencyKey, $ownerHash);
        }

        return new StoredPobImport(
            $id,
            $deletionToken,
            $expiresAt->format('Y-m-d\TH:i:s\Z'),
            false,
        );
    }

    public function delete(string $id, string $deletionToken): bool
    {
        $storedHash = DB::table('pob_imports')
            ->where('id', $id)
            ->value('deletion_token_hash_sha256');

        if (! is_string($storedHash)
            || ! hash_equals($storedHash, hash('sha256', $deletionToken))
        ) {
            return false;
        }

        return DB::table('pob_imports')->where('id', $id)->delete() === 1;
    }

    public function pruneExpired(): int
    {
        return DB::table('pob_imports')->where('expires_at', '<=', now())->delete();
    }

    public function erase(string $ownerId): void
    {
        DB::table('pob_imports')
            ->where('owner_id_hash', $this->keyedHash('pob-import-owner', $ownerId))
            ->delete();
    }

    private function replay(
        object $row,
        PobImportResult $result,
        string $requestHash,
        string $idempotencyKey,
        string $ownerHash,
    ): StoredPobImport {
        $data = get_object_vars($row);

        if ($this->string($data, 'owner_id_hash') !== $ownerHash
            || $this->string($data, 'request_hash_sha256') !== $requestHash
            || $this->string($data, 'input_checksum_sha256') !== $result->inputChecksumSha256
            || $this->string($data, 'game_edition') !== $result->canonicalBuild->edition->value
            || $this->string($data, 'parser_version') !== $result->parserVersion
        ) {
            throw new PobImportConflict;
        }

        $id = $this->string($data, 'id');
        $deletionToken = $this->deletionToken($id, $idempotencyKey, $ownerHash);

        if (! hash_equals($this->string($data, 'deletion_token_hash_sha256'), hash('sha256', $deletionToken))) {
            throw new PobImportConflict;
        }

        return new StoredPobImport(
            $id,
            $deletionToken,
            CarbonImmutable::parse($this->string($data, 'expires_at'))->utc()->format('Y-m-d\TH:i:s\Z'),
            true,
        );
    }

    private function deletionToken(string $id, string $idempotencyKey, string $ownerHash): string
    {
        return $this->keyedHash('pob-import-deletion:'.$id, $ownerHash."\0".$idempotencyKey);
    }

    private function keyedHash(string $purpose, string $value): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new \RuntimeException('The application encryption key is not configured.');
        }

        return hash_hmac('sha256', $purpose."\0".$value, $key);
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new \RuntimeException("Expected string PoB import field {$key}.");
        }

        return $value;
    }
}
