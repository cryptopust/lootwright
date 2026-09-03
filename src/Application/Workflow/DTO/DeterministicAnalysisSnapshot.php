<?php

namespace Lootwright\Application\Workflow\DTO;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\TradePlanning\ManualTradeRecipe;
use Lootwright\Domain\TradePlanning\TradeRecipe;

final readonly class DeterministicAnalysisSnapshot
{
    /**
     * @param  list<Finding>  $findings
     * @param  list<Recommendation>  $recommendations
     * @param  list<ManualTradeRecipe|TradeRecipe|\Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe>  $recipes
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
        public ?string $sourceId = null,
        public ?string $sourceVersion = null,
        public ?string $patchVersion = null,
        public ?string $league = null,
    ) {}
}
