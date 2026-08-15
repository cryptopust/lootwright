<?php

namespace Lootwright\Application\Analysis\DTO;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\TradePlanning\ManualTradeRecipe;

final readonly class DeterministicProducts
{
    /**
     * @param  list<Finding>  $findings
     * @param  list<Recommendation>  $recommendations
     * @param  list<ManualTradeRecipe>  $recipes
     */
    public function __construct(
        public array $findings,
        public array $recommendations,
        public array $recipes,
    ) {}
}
