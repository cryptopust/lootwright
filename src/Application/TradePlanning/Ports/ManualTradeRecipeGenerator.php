<?php

namespace Lootwright\Application\TradePlanning\Ports;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;

interface ManualTradeRecipeGenerator
{
    public function generate(ManualTradeRecipeRequest $request): ManualTradeRecipe;
}
