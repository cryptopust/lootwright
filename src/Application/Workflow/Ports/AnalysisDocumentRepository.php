<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Application\Workflow\DTO\AnalysisProvenanceStatus;
use Lootwright\Application\Workflow\DTO\PortableAnalysisDocument;

interface AnalysisDocumentRepository
{
    public function portableForOwner(string $analysisId, string $ownerId): ?PortableAnalysisDocument;

    public function provenanceForOwner(string $analysisId, string $ownerId): ?AnalysisProvenanceStatus;
}
