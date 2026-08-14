<?php

namespace Lootwright\Application\Workflow\Exception;

use Lootwright\Domain\PolicyProvenance\CapabilityDecision;

final class PolicyBlocked extends WorkflowException
{
    public function __construct(public readonly CapabilityDecision $decision)
    {
        parent::__construct('The Policy and Provenance Gate denied this workflow stage.');
    }
}
