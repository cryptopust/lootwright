<?php

namespace App\Modules\BuildIntake;

use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use RuntimeException;

final class PobPolicyDenied extends RuntimeException
{
    public function __construct(public readonly CapabilityDecision $decision)
    {
        parent::__construct('The requested import capability is not enabled.');
    }
}
