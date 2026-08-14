<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;

final readonly class CreateAnalysis
{
    public function __construct(
        private WorkflowRepository $repository,
        private IdentifierGenerator $identifiers,
        private WorkflowDispatcher $dispatcher,
    ) {}

    public function handle(string $ownerId, string $parentAnalysisId, AnalysisParameters $parameters): AnalysisRecord
    {
        $parent = $this->repository->analysisForOwner($parentAnalysisId, $ownerId);

        if ($parent === null) {
            throw new WorkflowNotFound('The analysis was not found.');
        }

        $snapshot = $parameters->canonicalJson();
        $analysis = $this->repository->createAnalysisVersion(
            $this->identifiers->uuid7(),
            $parent,
            $snapshot,
            hash('sha256', $snapshot),
        );
        $this->dispatcher->analyze($analysis->id);

        return $analysis;
    }
}
