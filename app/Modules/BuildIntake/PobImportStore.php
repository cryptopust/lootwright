<?php

namespace App\Modules\BuildIntake;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final class PobImportStore
{
    public function store(
        PobImportResult $result,
        string $requestHash,
        int $retentionHours,
    ): StoredPobImport {
        $maximum = max(1, (int) config('build-intake.maximum_retention_hours', 168));
        $retentionHours = max(1, min($retentionHours, $maximum));
        $id = (string) Str::uuid7();
        $deletionToken = bin2hex(random_bytes(32));
        $expiresAt = CarbonImmutable::now('UTC')->addHours($retentionHours);

        DB::table('pob_imports')->insert([
            'id' => $id,
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

        return new StoredPobImport(
            $id,
            $deletionToken,
            $expiresAt->format('Y-m-d\TH:i:s\Z'),
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
}
