<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class DeterministicAnalysisSnapshot
{
    public function __construct(
        public string $adapterKey,
        public string $parserVersion,
        public string $rulesetId,
        public string $rulesetVersion,
        public string $rulesetChecksumSha256,
        public string $inputSnapshot,
        public string $inputHashSha256,
        public string $outputSnapshot,
        public string $outputHashSha256,
    ) {}
}
