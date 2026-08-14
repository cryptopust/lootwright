<?php

namespace Lootwright\Application\PolicyProvenance;

use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class DecideCapability
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function handle(CapabilityRequest $request): DomainResult
    {
        return $this->policy->authorize($request);
    }
}
