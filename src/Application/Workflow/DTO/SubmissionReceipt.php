<?php

namespace Lootwright\Application\Workflow\DTO;

use Lootwright\Application\Workflow\AnalysisState;

final readonly class SubmissionReceipt
{
    public function __construct(
        public string $artifactId,
        public string $analysisId,
        public AnalysisState $state,
        public bool $replayed,
    ) {}
}
