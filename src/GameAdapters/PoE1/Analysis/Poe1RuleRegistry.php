<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

use Lootwright\Domain\Analysis\AnalysisRule;
use Lootwright\Domain\Analysis\FindingCategory;
use Lootwright\Domain\Analysis\RuleRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\RulesetVersion;
use RuntimeException;

final readonly class Poe1RuleRegistry implements RuleRegistry
{
    /** @var list<AnalysisRule> */
    private array $rules;

    /** @param array<int|string, true> $knownPassiveNodeIds */
    public function __construct(
        Poe1DeterministicAnalysisEngine $engine,
        Poe1AnalysisRuleset $configuration,
        array $knownPassiveNodeIds,
        private RulesetVersion $rulesetVersion,
    ) {
        if (! $rulesetVersion->belongsTo(GameEdition::Poe1)) {
            throw new RuntimeException('A PoE1 rule registry requires a PoE1 ruleset version.');
        }

        $rules = [];
        foreach ($configuration->ruleCodes as $ruleId) {
            $rules[] = new Poe1RegisteredRule(
                $ruleId,
                self::categoryFor($ruleId),
                $engine,
                $configuration,
                $knownPassiveNodeIds,
            );
        }
        $this->rules = $rules;
    }

    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    public function version(): RulesetVersion
    {
        return $this->rulesetVersion;
    }

    public function rules(): array
    {
        return $this->rules;
    }

    private static function categoryFor(string $ruleId): FindingCategory
    {
        return match (true) {
            str_starts_with($ruleId, 'data.') => FindingCategory::DataQuality,
            str_starts_with($ruleId, 'equipment.slot.') => FindingCategory::ItemConflicts,
            str_starts_with($ruleId, 'equipment.') => FindingCategory::Equipment,
            str_starts_with($ruleId, 'defence.') => FindingCategory::Resistances,
            str_starts_with($ruleId, 'resources.') => FindingCategory::AuraReservation,
            str_starts_with($ruleId, 'skills.') => FindingCategory::SkillConfiguration,
            str_starts_with($ruleId, 'passive_tree.') => FindingCategory::PassiveConflicts,
            str_starts_with($ruleId, 'recovery.') => FindingCategory::Recovery,
            str_starts_with($ruleId, 'offence.') => FindingCategory::Offence,
            default => throw new RuntimeException('Unclassified PoE1 analysis rule.'),
        };
    }
}
