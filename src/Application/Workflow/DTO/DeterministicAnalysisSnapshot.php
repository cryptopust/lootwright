<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class DeterministicAnalysisSnapshot
{
    /**
     * @param list<\Lootwright\Domain\Analysis\Finding> $findings
     * @param list<\Lootwright\Domain\Recommendations\Recommendation> $recommendations
     * @param list<\Lootwright\Domain\TradePlanning\ManualTradeRecipe|\Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe> $recipes
     */
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
        public array $findings = [],
        public array $recommendations = [],
        public array $recipes = [],
    ) {}
}
