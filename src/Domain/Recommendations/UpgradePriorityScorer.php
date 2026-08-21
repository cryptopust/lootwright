<?php

namespace Lootwright\Domain\Recommendations;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;

final readonly class UpgradePriorityScorer
{
    public function score(Finding $finding, UpgradeClassification $classification, BuildIntent $intent, int $constraintAdjustment = 0): int
    {
        $base = $finding->severity->value * 100;
        $class = match ($classification) {
            UpgradeClassification::Mandatory => 4_000,
            UpgradeClassification::Structural => 3_000,
            UpgradeClassification::HighImpact => 2_000,
            UpgradeClassification::Conditional => 1_000,
            UpgradeClassification::RequiresMarketCheck => 750,
            UpgradeClassification::Luxury => 250,
        };
        $goal = str_contains(strtolower($intent->goal->description), 'defence') && $finding->category->value === 'defence' ? 1_000 : 0;

        return max(0, min(100_000, $base + $class + $goal + $constraintAdjustment));
    }
}
