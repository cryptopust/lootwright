<?php

namespace Lootwright\GameAdapters\PoE2\TradePlanning;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\Exception\ManualRecipeGenerationFailed;

final class Poe2ManualTradeRecipeGenerator
{
    public function generate(ManualTradeRecipeRequest $request): ManualTradeRecipe
    {
        throw new ManualRecipeGenerationFailed(
            'poe2_manual_trade_inactive',
            'PoE2 manual Trade recipes remain inactive until the phase-two ruleset and policy gates are approved.',
        );
    }
}
