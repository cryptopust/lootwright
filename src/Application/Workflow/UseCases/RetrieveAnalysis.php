<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;

final readonly class RetrieveAnalysis
{
    public function __construct(private WorkflowRepository $repository) {}

    public function handle(string $ownerId, string $analysisId): AnalysisRecord
    {
        $analysis = $this->repository->analysisForOwner($analysisId, $ownerId);

        if ($analysis === null) {
            throw new WorkflowNotFound('The analysis was not found.');
        }

        return $analysis;
    }
}
