<?php

namespace Lootwright\Domain\TradePlanning;

use InvalidArgumentException;

final class RecipeValidator
{
    public function validate(TradeRecipe $recipe): void
    {
        if ($recipe->ruleset->edition !== $recipe->gameEdition) {
            throw new InvalidArgumentException('A Trade recipe ruleset must match its edition.');
        }
        if (str_contains($recipe->broadRecipe, '/api/') || str_contains($recipe->strictRecipe, '/api/')) {
            throw new InvalidArgumentException('Trade recipes cannot contain API request paths.');
        }
        foreach ([...$recipe->requiredModifiers, ...$recipe->optionalModifiers, ...$recipe->excludedModifiers] as $filter) {
            if (array_key_exists('documented_identifier', $filter) && $filter['documented_identifier'] !== null) {
                throw new InvalidArgumentException('Documented identifiers are evidence only and cannot become request payloads.');
            }
        }
    }
}
