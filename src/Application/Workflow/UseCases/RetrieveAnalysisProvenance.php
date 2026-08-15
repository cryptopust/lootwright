<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisProvenanceStatus;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\Ports\AnalysisDocumentRepository;

final readonly class RetrieveAnalysisProvenance
{
    public function __construct(private AnalysisDocumentRepository $repository) {}

    public function handle(string $ownerId, string $analysisId): AnalysisProvenanceStatus
    {
        return $this->repository->provenanceForOwner($analysisId, $ownerId)
            ?? throw new WorkflowNotFound('The analysis provenance was not found.');
    }
}
