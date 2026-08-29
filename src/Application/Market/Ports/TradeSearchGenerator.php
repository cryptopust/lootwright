<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Application\Market\TradeSearchMode;
use Lootwright\Application\Market\TradeSearchPlan;
use Lootwright\Domain\TradePlanning\TradeRecipe;

interface TradeSearchGenerator
{
    public function generate(TradeRecipe $recipe, string $league, TradeSearchMode $mode): TradeSearchPlan;
}
