<?php

namespace Lootwright\Domain\Recommendations\Ports;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Shared\Error\DomainResult;

interface UpgradePlanner
{
    /**
     * @param  list<Finding>  $findings
     */
    public function plan(array $findings, BuildIntent $intent): DomainResult;
}
