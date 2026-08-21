<?php

namespace App\Modules\AI;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\AIGateway\Ports\AiRuntimePolicy;

final readonly class DatabaseAiRuntimePolicy implements AiRuntimePolicy
{
    public function permits(string $task): bool
    {
        if (! (bool) config('ai.enabled')) {
            return false;
        }

        $control = DB::table('ai_runtime_controls')->where('scope', 'global')->first();
        if ($control === null || ! (bool) $control->global_enabled) {
            return false;
        }

        return match ($task) {
            'intent', 'clarification' => (bool) config('ai.intent_enabled') && (bool) $control->intent_enabled,
            'explanation' => (bool) config('ai.explanation_enabled') && (bool) $control->explanation_enabled,
            default => false,
        };
    }
}
