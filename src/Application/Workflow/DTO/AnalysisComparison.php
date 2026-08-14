<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class AnalysisComparison
{
    public function __construct(
        public string $leftAnalysisId,
        public string $rightAnalysisId,
        public bool $inputChanged,
        public bool $outputChanged,
        public bool $rulesetChanged,
        public ?string $leftOutputHash,
        public ?string $rightOutputHash,
    ) {}
}
