<?php

namespace Lootwright\Domain\PolicyProvenance\Ports;

use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\Shared\Error\DomainResult;

interface CapabilityPolicy
{
    public function authorize(CapabilityRequest $request): DomainResult;
}
