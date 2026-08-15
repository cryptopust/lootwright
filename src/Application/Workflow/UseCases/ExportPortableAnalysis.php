<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\PortableAnalysisExport;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\Ports\AnalysisDocumentRepository;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class ExportPortableAnalysis
{
    public function __construct(private AnalysisDocumentRepository $repository) {}

    public function handle(string $ownerId, string $analysisId): PortableAnalysisExport
    {
        $document = $this->repository->portableForOwner($analysisId, $ownerId);

        if ($document === null) {
            throw new WorkflowNotFound('The completed analysis was not found.');
        }

        $json = CanonicalJson::encode($document);

        return new PortableAnalysisExport($document, $json, hash('sha256', $json));
    }
}
