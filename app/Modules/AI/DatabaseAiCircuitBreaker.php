<?php

namespace App\Modules\AI;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\AIGateway\Ports\AiCircuitBreaker;

final readonly class DatabaseAiCircuitBreaker implements AiCircuitBreaker
{
    public function __construct(
        private int $failureThreshold,
        private int $cooldownSeconds,
    ) {}

    public function permitsRequest(): bool
    {
        return DB::transaction(function (): bool {
            $control = DB::table('ai_runtime_controls')->where('scope', 'global')->lockForUpdate()->first();
            if ($control === null) {
                return false;
            }
            if ($control->circuit_open_until === null) {
                return true;
            }
            if (now()->lessThan($control->circuit_open_until)) {
                return false;
            }

            // Reserve the single half-open probe. Success closes the circuit;
            // failure extends it through recordFailure().
            DB::table('ai_runtime_controls')->where('scope', 'global')->update([
                'circuit_open_until' => now()->addSeconds($this->cooldownSeconds),
                'updated_at' => now(),
            ]);

            return true;
        }, 3);
    }

    public function recordSuccess(): void
    {
        DB::table('ai_runtime_controls')->where('scope', 'global')->update([
            'consecutive_provider_failures' => 0,
            'circuit_open_until' => null,
            'updated_at' => now(),
        ]);
    }

    public function recordFailure(): void
    {
        if ($this->failureThreshold < 1 || $this->cooldownSeconds < 1) {
            return;
        }

        DB::transaction(function (): void {
            $control = DB::table('ai_runtime_controls')->where('scope', 'global')->lockForUpdate()->first();
            if ($control === null) {
                return;
            }

            $failures = min(65_535, (int) $control->consecutive_provider_failures + 1);
            DB::table('ai_runtime_controls')->where('scope', 'global')->update([
                'consecutive_provider_failures' => $failures,
                'circuit_open_until' => $failures >= $this->failureThreshold ? now()->addSeconds($this->cooldownSeconds) : null,
                'updated_at' => now(),
            ]);
        }, 3);
    }
}
