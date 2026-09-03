<?php

namespace Lootwright\Domain\Recommendations\Ports;

use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Shared\Error\DomainResult;

interface UpgradePlanner
{
    /**
     * The array input preserves the pre-graph application seam while callers
     * migrate. Production graph planning requires AnalysisResult.
     *
     * @param  list<Finding>|AnalysisResult  $analysis
     */
    public function plan(
        array|AnalysisResult $analysis,
        BuildIntent $intent,
        ?BudgetConstraint $budget = null,
        ?UserConstraints $constraints = null,
    ): DomainResult;
}
