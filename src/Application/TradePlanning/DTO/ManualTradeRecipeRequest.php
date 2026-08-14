<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Version\LeagueId;

final readonly class ManualTradeRecipeRequest
{
    public function __construct(
        public GameScope $scope,
        public ?LeagueId $league,
        public ?Budget $budget,
        public RulesetIdentity $ruleset,
        public ApprovedTradeVocabulary $vocabulary,
        public SlotUpgradePlan $plan,
    ) {}
}
