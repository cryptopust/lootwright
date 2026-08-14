<?php

namespace Lootwright\Application\TradePlanning\Ports;

use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;

interface ManualTradeRecipePolicy
{
    public function authorize(GameEdition $edition, RulesetIdentity $ruleset): void;
}
