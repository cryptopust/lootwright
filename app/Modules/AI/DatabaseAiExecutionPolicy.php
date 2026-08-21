<?php

namespace App\Modules\AI;

use Carbon\CarbonImmutable;
use Lootwright\Application\AIGateway\Ports\AiExecutionPolicy;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;

final readonly class DatabaseAiExecutionPolicy implements AiExecutionPolicy
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function permits(string $task): bool
    {
        if ($task === 'intent' || $task === 'clarification') {
            if (! (bool) config('source-governance.openai_intent_enabled', false)) {
                return false;
            }
        }
        if ($task === 'explanation' && ! (bool) config('source-governance.openai_explanations_enabled', false)) {
            return false;
        }

        $operation = match ($task) {
            'intent', 'clarification' => 'openai.responses.intent',
            'explanation' => 'openai.responses.explanation',
            default => null,
        };

        if ($operation === null) {
            return false;
        }

        $time = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'));
        if ($time->isFailure() || ! $time->value() instanceof RetrievedAt) {
            return false;
        }

        $request = CapabilityRequest::create(
            Capability::LiveFetch,
            $operation,
            'OPENAI-API',
            '2026-08-15',
            $time->value(),
            ['configured_credentials', 'current_policy_evidence', 'data_minimization', 'privacy_disclosure', 'provider_approved', 'spend_limit_configured'],
        );

        if ($request->isFailure() || ! $request->value() instanceof CapabilityRequest) {
            return false;
        }

        $decision = $this->policy->authorize($request->value());

        return $decision->isSuccess()
            && $decision->value() instanceof CapabilityDecision
            && $decision->value()->permitsExecution();
    }
}
