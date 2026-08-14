<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;

final readonly class ReanalyzeWithGoalsOrBudget
{
    public function __construct(private CreateAnalysis $createAnalysis) {}

    public function handle(
        string $ownerId,
        string $analysisId,
        AnalysisParameters $parameters,
    ): AnalysisRecord {
        if ($parameters->goals === [] && $parameters->budgetAmount === null) {
            throw new InvalidWorkflowInput('Reanalysis requires at least one goal or a budget.');
        }

        return $this->createAnalysis->handle($ownerId, $analysisId, $parameters);
    }
}
