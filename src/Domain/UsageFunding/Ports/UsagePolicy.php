<?php

namespace Lootwright\Domain\UsageFunding\Ports;

use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

interface UsagePolicy
{
    public function mayUse(GameEdition $edition, Capability $capability): DomainResult;
}
