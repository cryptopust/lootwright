<?php

namespace Lootwright\Application\Analysis\UseCases;

use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\UpgradeGraph;
use Lootwright\Domain\Recommendations\UserConstraints;
use RuntimeException;

final readonly class PlanDeterministicUpgrades
{
    public function __construct(private UpgradePlanner $planner) {}

    public function handle(
        AnalysisResult $analysis,
        BuildIntent $intent,
        BudgetConstraint $budget,
        UserConstraints $constraints = new UserConstraints,
    ): UpgradeGraph {
        $result = $this->planner->plan($analysis, $intent, $budget, $constraints);
        if ($result->isFailure() || ! $result->value() instanceof UpgradeGraph) {
            throw new RuntimeException($result->isFailure() ? $result->error()->message : 'The upgrade planner returned an invalid graph.');
        }

        return $result->value();
    }
}
