<?php

namespace App\Modules\TradePlanning;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipeGenerator;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\PoE1\TradePlanning\Poe1ManualTradeRecipeGenerator;
use Lootwright\GameAdapters\PoE2\TradePlanning\Poe2ManualTradeRecipeGenerator;

final readonly class EditionManualTradeRecipeGenerator implements ManualTradeRecipeGenerator
{
    public function __construct(
        private Poe1ManualTradeRecipeGenerator $poe1,
        private Poe2ManualTradeRecipeGenerator $poe2,
    ) {}

    public function generate(ManualTradeRecipeRequest $request): ManualTradeRecipe
    {
        return match ($request->scope->edition) {
            GameEdition::Poe1 => $this->poe1->generate($request),
            GameEdition::Poe2 => $this->poe2->generate($request),
        };
    }
}
