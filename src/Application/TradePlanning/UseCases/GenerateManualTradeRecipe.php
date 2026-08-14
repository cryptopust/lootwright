<?php

namespace Lootwright\Application\TradePlanning\UseCases;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipeGenerator;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipePolicy;

final readonly class GenerateManualTradeRecipe
{
    public function __construct(
        private ManualTradeRecipePolicy $policy,
        private ManualTradeRecipeGenerator $generator,
    ) {}

    public function handle(ManualTradeRecipeRequest $request): ManualTradeRecipe
    {
        $this->policy->authorize($request->scope->edition, $request->ruleset);

        return $this->generator->generate($request);
    }
}
