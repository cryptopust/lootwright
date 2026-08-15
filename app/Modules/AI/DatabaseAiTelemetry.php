<?php

namespace App\Modules\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\AIGateway\DTO\AiCallAudit;
use Lootwright\Application\AIGateway\Ports\AiTelemetry;

final readonly class DatabaseAiTelemetry implements AiTelemetry
{
    public function record(AiCallAudit $audit): void
    {
        DB::table('ai_request_audits')->insert([
            'id' => (string) Str::uuid7(),
            'request_hash' => $audit->requestHash,
            'user_hash' => $audit->userHash,
            'prompt_template_version' => $audit->promptTemplateVersion,
            'provider' => $audit->provider,
            'model' => $audit->model,
            'task' => $audit->task,
            'input_tokens' => $audit->inputTokens,
            'cached_input_tokens' => $audit->cachedInputTokens,
            'output_tokens' => $audit->outputTokens,
            'latency_ms' => $audit->latencyMs,
            'cache_status' => $audit->cacheStatus,
            'validation_outcome' => $audit->validationOutcome,
            'repair_attempts' => $audit->repairAttempts,
            'cost_micro_usd' => $audit->costMicroUsd,
            'created_at' => now(),
        ]);
    }
}
