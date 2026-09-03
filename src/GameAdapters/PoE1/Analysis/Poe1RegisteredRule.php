<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

use Lootwright\Domain\Analysis\AnalysisContext;
use Lootwright\Domain\Analysis\AnalysisRule;
use Lootwright\Domain\Analysis\FindingCategory;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Import\PropertySupportStatus;

final readonly class Poe1RegisteredRule implements AnalysisRule
{
    /** @param array<int|string, true> $knownPassiveNodeIds */
    public function __construct(
        private string $ruleId,
        private FindingCategory $findingCategory,
        private Poe1DeterministicAnalysisEngine $engine,
        private Poe1AnalysisRuleset $configuration,
        private array $knownPassiveNodeIds,
    ) {}

    public function id(): string
    {
        return $this->ruleId;
    }

    public function category(): FindingCategory
    {
        return $this->findingCategory;
    }

    public function evaluate(AnalysisContext $context): array
    {
        if ($context->build instanceof CanonicalImportedBuild) {
            $property = match (true) {
                str_starts_with($this->ruleId, 'equipment.') => 'items',
                str_starts_with($this->ruleId, 'skills.') => 'skills',
                str_starts_with($this->ruleId, 'passive_tree.') => 'passive_nodes',
                str_contains($this->ruleId, '.level.') => 'level',
                str_contains($this->ruleId, '.class.') => 'class',
                str_contains($this->ruleId, '.ascendancy.') => 'ascendancy',
                default => null,
            };
            if ($property !== null && ($context->build->propertySupport[$property] ?? null) === PropertySupportStatus::Unsupported) {
                return [];
            }
        }

        return $this->engine->evaluateRule(
            $this->ruleId,
            $context,
            $this->configuration,
            $this->knownPassiveNodeIds,
        );
    }
}
