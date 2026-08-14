<?php

namespace Lootwright\Application\TradePlanning\Serialization;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final class ManualTradeRecipeSerializer
{
    private function __construct() {}

    public static function canonicalJson(ManualTradeRecipe $recipe): string
    {
        return CanonicalJson::encode($recipe);
    }
}
