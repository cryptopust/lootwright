<?php

namespace App\Modules\ExternalSources;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\ExternalSources\Ports\SourceUpdateObserver;
use Lootwright\Domain\Shared\Game\GameEdition;

final class DatabaseSourceUpdateObserver implements SourceUpdateObserver
{
    public function latestChecksum(string $sourceCode, GameEdition $edition): ?string
    {
        $checksum = DB::table('source_snapshots')
            ->where('source_code', $sourceCode)
            ->where('game_edition', $edition->value)
            ->where('status', 'valid')
            ->latest('retrieved_at')
            ->value('checksum_sha256');

        return is_string($checksum) ? $checksum : null;
    }

    public function record(
        string $sourceCode,
        GameEdition $edition,
        string $sourceVersion,
        ?string $previousChecksumSha256,
        ?string $observedChecksumSha256,
        string $status,
        ?string $failureCode = null,
    ): void {
        if (! in_array($status, ['unchanged', 'changed_staged', 'failed', 'disabled'], true)
            || ! $this->checksumIsValid($previousChecksumSha256)
            || ! $this->checksumIsValid($observedChecksumSha256)
            || ($failureCode !== null && preg_match('/^[a-z][a-z0-9_]{1,95}$/D', $failureCode) !== 1)
        ) {
            throw new DomainException('The source update observation is invalid.');
        }

        $now = now();
        DB::table('source_update_observations')->insert([
            'id' => (string) Str::uuid7(),
            'source_code' => $sourceCode,
            'game_edition' => $edition->value,
            'source_version' => $sourceVersion,
            'previous_checksum_sha256' => $previousChecksumSha256,
            'observed_checksum_sha256' => $observedChecksumSha256,
            'status' => $status,
            'failure_code' => $failureCode,
            'checked_at' => $now,
            'created_at' => $now,
        ]);
    }

    private function checksumIsValid(?string $checksum): bool
    {
        return $checksum === null || preg_match('/^[0-9a-f]{64}$/D', $checksum) === 1;
    }
}
