<?php

namespace App\Modules\AI;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\AIGateway\DTO\ExplanationBundle;
use Lootwright\Application\AIGateway\Ports\AnalysisExplanationRepository;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use RuntimeException;

final class PostgresAnalysisExplanationRepository implements AnalysisExplanationRepository
{
    public function storeForOwner(
        string $analysisId,
        string $ownerId,
        ExplanationBundle $bundle,
        string $status,
    ): bool {
        $analysis = DB::table('analyses')
            ->where('id', $analysisId)
            ->where('owner_id_hash', $this->ownerHash($ownerId))
            ->where('state', 'completed')
            ->first();

        if ($analysis === null
            || array_column($bundle->findings, 'code') !== $this->codes('analysis_findings', $analysisId)
            || array_column($bundle->recommendations, 'code') !== $this->codes('analysis_recommendations', $analysisId)
        ) {
            return false;
        }

        $payload = CanonicalJson::encode($bundle);
        $hash = hash('sha256', $payload);
        $existing = DB::table('analysis_explanations')->where('analysis_id', $analysisId)->first();

        if ($existing !== null) {
            return is_string($existing->payload_hash_sha256) && hash_equals($existing->payload_hash_sha256, $hash);
        }

        DB::table('analysis_explanations')->insert([
            'id' => (string) Str::uuid7(),
            'analysis_id' => $analysisId,
            'status' => $status,
            'payload_encrypted' => Crypt::encryptString($payload),
            'payload_hash_sha256' => $hash,
            'created_at' => now(),
        ]);

        return true;
    }

    /** @return list<string> */
    private function codes(string $table, string $analysisId): array
    {
        return array_values(DB::table($table)->where('analysis_id', $analysisId)->orderBy('sequence')->pluck('code')
            ->filter(static fn (mixed $code): bool => is_string($code))
            ->values()
            ->all());
    }

    private function ownerHash(string $ownerId): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('The application encryption key is not configured.');
        }

        return hash_hmac('sha256', "analysis-owner\0".$ownerId, $key);
    }
}
