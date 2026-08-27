<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use Lootwright\Domain\Analysis\AnalysisContext;
use Lootwright\Domain\Analysis\AnalysisRule;
use Lootwright\Domain\Analysis\FindingCategory;

/** Registry metadata object; evaluation is delegated to the PoE2 engine. */
final readonly class Poe2RegisteredRule implements AnalysisRule
{
    public function __construct(private string $ruleId) {}

    public function id(): string
    {
        return $this->ruleId;
    }

    public function category(): FindingCategory
    {
        return str_starts_with($this->ruleId, 'poe2.skills.') ? FindingCategory::Skills
            : (str_starts_with($this->ruleId, 'poe2.data.resistances') ? FindingCategory::Resistances : FindingCategory::DataQuality);
    }

    public function evaluate(AnalysisContext $context): array
    {
        return [];
    }
}
