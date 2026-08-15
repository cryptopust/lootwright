<?php

namespace App\Modules\Analysis\Infrastructure;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Application\Workflow\Ports\AnalysisPolicyGate;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\PolicyVersion;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use RuntimeException;

final readonly class DatabaseAnalysisPolicyGate implements AnalysisPolicyGate
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function authorize(ResolvedAnalysisContext $context): void
    {
        if (! (bool) config('security.emergency.rulesets')) {
            throw new PolicyBlocked(new CapabilityDecision(
                Capability::DerivativeAnalysis,
                $context->sourceId,
                PolicyDecision::Deny,
                PolicyDecisionReason::GlobalKillSwitch,
                PolicyVersion::baseline(),
                'Ruleset execution is disabled by the emergency switch.',
            ));
        }

        foreach (['ruleset.deterministic_analysis', 'trade.manual_recipe.generate'] as $operation) {
            $timestamp = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'));

            if ($timestamp->isFailure() || ! $timestamp->value() instanceof RetrievedAt) {
                throw new RuntimeException('The policy timestamp is invalid.');
            }

            $request = CapabilityRequest::create(
                Capability::DerivativeAnalysis,
                $operation,
                $context->sourceId,
                $context->sourceVersion,
                $timestamp->value(),
                ['checksum_verified', 'exact_ruleset_resolved', 'manual_actions_only'],
            );

            if ($request->isFailure() || ! $request->value() instanceof CapabilityRequest) {
                throw new RuntimeException('The analysis capability request is invalid.');
            }

            $decision = $this->policy->authorize($request->value());

            if ($decision->isFailure() || ! $decision->value() instanceof CapabilityDecision) {
                throw new RuntimeException('The Policy and Provenance Gate returned an invalid decision.');
            }

            $resolvedDecision = $decision->value();

            if ($context->analysisId !== null) {
                DB::table('analysis_policy_decisions')->updateOrInsert(
                    [
                        'analysis_id' => $context->analysisId,
                        'capability' => $resolvedDecision->capability->value,
                        'operation' => $operation,
                    ],
                    [
                        'source_id' => $context->sourceId,
                        'source_version' => $context->sourceVersion,
                        'decision' => $resolvedDecision->decision->value,
                        'reason' => $resolvedDecision->reason->value,
                        'policy_version' => $resolvedDecision->policyVersion->value,
                        'evidence_ids' => json_encode($resolvedDecision->evidenceIds, JSON_THROW_ON_ERROR),
                        'evaluated_at' => $timestamp->value()->value,
                    ],
                );
            }

            if (! $resolvedDecision->permitsExecution()) {
                throw new PolicyBlocked($resolvedDecision);
            }
        }
    }
}
