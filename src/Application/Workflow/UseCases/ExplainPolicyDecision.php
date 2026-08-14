<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use RuntimeException;

final readonly class ExplainPolicyDecision
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function handle(CapabilityRequest $request): CapabilityDecision
    {
        $result = $this->policy->authorize($request);

        if ($result->isFailure() || ! $result->value() instanceof CapabilityDecision) {
            throw new RuntimeException('The Policy and Provenance Gate returned an invalid decision.');
        }

        return $result->value();
    }
}
