<?php

namespace Lootwright\Application\TradePlanning\Exception;

use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use RuntimeException;

final class ManualTradePolicyBlocked extends RuntimeException
{
    public function __construct(public readonly CapabilityDecision $decision)
    {
        parent::__construct('The Policy and Provenance Gate denied manual Trade recipe generation.');
    }
}
