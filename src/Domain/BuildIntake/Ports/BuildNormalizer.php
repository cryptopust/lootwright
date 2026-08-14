<?php

namespace Lootwright\Domain\BuildIntake\Ports;

use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainResult;

interface BuildNormalizer
{
    public function normalize(BuildSnapshot $snapshot, RulesetIdentity $ruleset): DomainResult;
}
