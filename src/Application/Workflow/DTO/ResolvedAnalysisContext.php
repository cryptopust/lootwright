<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class ResolvedAnalysisContext
{
    public function __construct(
        public string $adapterKey,
        public string $parserVersion,
        public string $rulesetId,
        public string $rulesetVersion,
        public string $rulesetChecksumSha256,
        public string $sourceId,
        public string $sourceVersion,
        public ?string $patchVersion = null,
        public ?string $league = null,
        public ?string $analysisId = null,
    ) {}
}
