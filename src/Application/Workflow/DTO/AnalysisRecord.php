<?php

namespace Lootwright\Application\Workflow\DTO;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class AnalysisRecord
{
    public function __construct(
        public string $id,
        public string $artifactId,
        public string $ownerId,
        public GameEdition $edition,
        public int $version,
        public AnalysisState $state,
        public string $parametersSnapshot,
        public string $parametersHashSha256,
        public ?string $parentAnalysisId = null,
        public ?string $adapterKey = null,
        public ?string $parserVersion = null,
        public ?string $rulesetId = null,
        public ?string $rulesetVersion = null,
        public ?string $rulesetChecksumSha256 = null,
        public ?string $inputSnapshot = null,
        public ?string $inputHashSha256 = null,
        public ?string $outputSnapshot = null,
        public ?string $outputHashSha256 = null,
        public ?string $clarificationSnapshot = null,
        public ?string $failureCode = null,
    ) {}
}
