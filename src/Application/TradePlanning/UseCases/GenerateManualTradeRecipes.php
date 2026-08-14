<?php

namespace Lootwright\Application\TradePlanning\UseCases;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\Exception\ManualRecipeGenerationFailed;

final readonly class GenerateManualTradeRecipes
{
    public function __construct(private GenerateManualTradeRecipe $generateOne) {}

    /**
     * @param  list<ManualTradeRecipeRequest>  $requests
     * @return list<ManualTradeRecipe>
     */
    public function handle(array $requests): array
    {
        $recipes = [];
        $slots = [];

        foreach ($requests as $request) {
            $key = $request->scope->edition->value.':'.$request->plan->slot->value;

            if (isset($slots[$key])) {
                throw new ManualRecipeGenerationFailed(
                    'duplicate_equipment_slot',
                    'Each recommended equipment slot may produce only one manual recipe.',
                );
            }

            $slots[$key] = true;
            $recipes[] = $this->generateOne->handle($request);
        }

        return $recipes;
    }
}
