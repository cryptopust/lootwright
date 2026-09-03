<?php

namespace Lootwright\Application\TradePlanning\UseCases;

use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipePolicy;
use Lootwright\Application\TradePlanning\TradeRecipeBuilder;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\Recommendations\UpgradeCandidate;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\TradePlanning\TradeRecipe;
use Lootwright\Domain\TradePlanning\TradeVocabulary;

final readonly class GenerateTradeRecipe
{
    public function __construct(
        private ManualTradeRecipePolicy $policy,
        private TradeRecipeBuilder $builder,
    ) {}

    public function handle(
        UpgradeCandidate $candidate,
        BuildSnapshot $build,
        GameRuleset $ruleset,
        TradeVocabulary $vocabulary,
    ): TradeRecipe {
        $this->policy->authorize($build->scope->edition, $ruleset->identity);

        return $this->builder->build($candidate, $build, $ruleset, $vocabulary);
    }
}
