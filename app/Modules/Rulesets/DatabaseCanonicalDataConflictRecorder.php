<?php

namespace App\Modules\Rulesets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\GameData\DTO\CanonicalDataConflict;
use Lootwright\Application\GameData\Ports\CanonicalDataConflictRecorder;

final class DatabaseCanonicalDataConflictRecorder implements CanonicalDataConflictRecorder
{
    public function record(CanonicalDataConflict $conflict, ?string $rulesetVersionId = null): void
    {
        $left = $conflict->left->record;
        $right = $conflict->right->record;
        $identity = [
            'game_edition' => $conflict->edition->value,
            'data_category' => $conflict->category->value,
            'external_id' => $conflict->externalId,
            'left_source_code' => $left->provenance->sourceCode,
            'left_checksum_sha256' => $left->checksumSha256,
            'right_source_code' => $right->provenance->sourceCode,
            'right_checksum_sha256' => $right->checksumSha256,
        ];
        if (DB::table('canonical_data_conflicts')->where($identity)->exists()) {
            return;
        }
        DB::table('canonical_data_conflicts')->insert([
            'id' => (string) Str::uuid7(),
            ...$identity,
            'ruleset_version_id' => $rulesetVersionId,
            'reason_code' => $conflict->reasonCode,
            'status' => 'quarantined',
            'detected_at' => now(),
            'created_at' => now(),
        ]);
    }
}
