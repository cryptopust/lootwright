<?php

namespace App\Modules\Analysis\Infrastructure;

use Carbon\CarbonImmutable;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Application\Workflow\Ports\AnalysisPolicyGate;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use RuntimeException;

final readonly class DatabaseAnalysisPolicyGate implements AnalysisPolicyGate
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function authorize(ResolvedAnalysisContext $context): void
    {
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

            if (! $decision->value()->permitsExecution()) {
                throw new PolicyBlocked($decision->value());
            }
        }
    }
}
