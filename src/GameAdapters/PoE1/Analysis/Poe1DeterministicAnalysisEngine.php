<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

use Lootwright\Domain\Analysis\AnalysisContext;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingCategory;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use RuntimeException;

final readonly class Poe1DeterministicAnalysisEngine
{
    public const ENGINE_VERSION = '1.0.0';

    /** @var list<string> */
    public const RULE_CODES = [
        'data.character.level.missing',
        'data.character.class.missing',
        'data.character.ascendancy.missing',
        'equipment.required_slot.empty',
        'defence.fire_resistance.below_reported_max',
        'defence.cold_resistance.below_reported_max',
        'defence.lightning_resistance.below_reported_max',
        'defence.chaos_resistance.negative',
        'resources.mana.unreserved_negative',
        'resources.mana.reservation_invalid',
        'skills.gem.disabled',
        'skills.main.insufficient_links',
        'equipment.slot.conflict',
        'passive_tree.node.unknown',
        'defence.life.below_content_profile',
        'defence.energy_shield.below_content_profile',
        'defence.armour.low',
        'defence.evasion.low',
        'defence.block.low',
        'defence.spell_block.low',
        'defence.suppression.low',
        'recovery.life.missing',
        'recovery.life.leech_missing',
        'recovery.life.regeneration_missing',
        'skills.support.unknown',
        'skills.main.unknown',
        'offence.crit.resolute_technique_conflict',
        'offence.noncrit.crit_scaling_conflict',
        'passive_tree.keystone.conflict',
        'equipment.weapon.skill_incompatible',
        'skills.aura.conflict',
        'attributes.requirement.missing',
        'resources.mana.cost_unsustainable',
    ];

    /**
     * @param  array<int|string, true>  $knownPassiveNodeIds
     * @param  array<string, mixed>  $sourceProvenance
     * @return list<Finding>
     */
    public function analyze(
        CanonicalImportedBuild $build,
        AnalysisId $analysisId,
        RulesetIdentity $ruleset,
        Poe1AnalysisRuleset $analysisRules,
        array $knownPassiveNodeIds,
        array $sourceProvenance,
    ): array {
        if ($build->edition !== $ruleset->edition || ! $analysisId->belongsTo($ruleset->edition)) {
            throw new RuntimeException('The build, analysis, and ruleset must share a game edition.');
        }

        $findings = [];
        $this->missingCharacterData($build, $analysisId, $ruleset, $sourceProvenance, $findings);
        $this->equipment($build, $analysisId, $ruleset, $analysisRules, $sourceProvenance, $findings);
        $this->resistances($build, $analysisId, $ruleset, $analysisRules, $sourceProvenance, $findings);
        $this->mana($build, $analysisId, $ruleset, $analysisRules, $sourceProvenance, $findings);
        $this->skills($build, $analysisId, $ruleset, $analysisRules, $sourceProvenance, $findings);
        $this->passives($build, $analysisId, $ruleset, $knownPassiveNodeIds, $sourceProvenance, $findings);
        $this->defensiveProfile($build, $analysisId, $ruleset, $analysisRules, $sourceProvenance, $findings);
        $this->recoveryAndOffence($build, $analysisId, $ruleset, $sourceProvenance, $findings);
        $this->attributesAndSustainability($build, $analysisId, $ruleset, $analysisRules, $sourceProvenance, $findings);

        $order = array_flip(self::RULE_CODES);
        usort($findings, static fn (Finding $left, Finding $right): int => ($order[$left->code] ?? 999) <=> ($order[$right->code] ?? 999));

        return $findings;
    }

    /**
     * Registry-facing evaluation hook. The underlying PoE1 implementation
     * remains the single source of rule semantics; registries only expose an
     * edition-scoped, versioned catalogue of those rules.
     *
     * @param  array<int|string, true>  $knownPassiveNodeIds
     * @return list<Finding>
     */
    public function evaluateRule(
        string $ruleId,
        AnalysisContext $context,
        Poe1AnalysisRuleset $analysisRules,
        array $knownPassiveNodeIds,
    ): array {
        if (! $context->build instanceof CanonicalImportedBuild) {
            return [];
        }

        $all = [];
        $identity = $context->ruleset->identity;
        if (str_starts_with($ruleId, 'data.')) {
            $this->missingCharacterData($context->build, $context->analysisId, $identity, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'equipment.')) {
            $this->equipment($context->build, $context->analysisId, $identity, $analysisRules, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'defence.')) {
            $this->resistances($context->build, $context->analysisId, $identity, $analysisRules, $context->sourceProvenance, $all);
            $this->defensiveProfile(
                $context->build,
                $context->analysisId,
                $identity,
                $analysisRules,
                $context->sourceProvenance,
                $all,
                $context->intent->goal->contentGoal->value,
            );
        } elseif (str_starts_with($ruleId, 'resources.mana.cost_')) {
            $this->attributesAndSustainability($context->build, $context->analysisId, $identity, $analysisRules, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'resources.')) {
            $this->mana($context->build, $context->analysisId, $identity, $analysisRules, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'skills.')) {
            $this->skills($context->build, $context->analysisId, $identity, $analysisRules, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'passive_tree.')) {
            $this->passives($context->build, $context->analysisId, $identity, $knownPassiveNodeIds, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'recovery.') || str_starts_with($ruleId, 'offence.')) {
            $this->recoveryAndOffence($context->build, $context->analysisId, $identity, $context->sourceProvenance, $all);
        } elseif (str_starts_with($ruleId, 'attributes.')) {
            $this->attributesAndSustainability($context->build, $context->analysisId, $identity, $analysisRules, $context->sourceProvenance, $all);
        } else {
            throw new RuntimeException('The PoE1 registry requested an unknown rule.');
        }

        return array_values(array_filter($all, static fn (Finding $finding): bool => $finding->code === $ruleId));
    }

    /**
     * @param  array<string, mixed>  $provenance
     * @param  list<Finding>  $findings
     */
    private function missingCharacterData(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, array $provenance, array &$findings): void
    {
        foreach ([
            ['value' => $build->characterLevel, 'code' => 'data.character.level.missing', 'title' => 'Character level is missing', 'key' => 'build:character_level'],
            ['value' => $build->characterClassId, 'code' => 'data.character.class.missing', 'title' => 'Character class is missing', 'key' => 'build:character_class'],
            ['value' => $build->ascendancyId, 'code' => 'data.character.ascendancy.missing', 'title' => 'Ascendancy is missing', 'key' => 'build:ascendancy'],
        ] as $rule) {
            if ($rule['value'] === null) {
                $findings[] = $this->finding($id, $ruleset, $rule['code'], FindingSeverity::Information, FindingCategory::DataQuality, $rule['title'], 'The normalized PoB field is absent; Lootwright leaves it unknown.', null, 'reported value', [], [], [], [$rule['key']], $provenance, 10_000);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $provenance
     * @param  list<Finding>  $findings
     */
    private function equipment(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, Poe1AnalysisRuleset $analysisRules, array $provenance, array &$findings): void
    {
        $slotItems = [];
        foreach ($build->items as $item) {
            $itemId = is_string($item['id'] ?? null) ? $item['id'] : 'unknown-item';
            foreach (is_array($item['slots'] ?? null) ? $item['slots'] : [] as $slot) {
                if (! is_string($slot) || trim($slot) === '') {
                    continue;
                }
                $normalized = $this->normalizeSlot($slot, $analysisRules->requiredSlotAliases);
                $slotItems[$normalized][] = $itemId;
            }
        }
        ksort($slotItems, SORT_STRING);
        $missing = [];
        foreach (array_keys($analysisRules->requiredSlotAliases) as $required) {
            if (! isset($slotItems[$required])) {
                $missing[] = $required;
            }
        }
        if ($missing !== []) {
            $findings[] = $this->finding($id, $ruleset, 'equipment.required_slot.empty', FindingSeverity::Warning, FindingCategory::Equipment, 'Required equipment slots are empty', 'The normalized PoB has no assigned item in one or more core armour slots defined by this ruleset.', $missing, 'assigned item', $missing, [], [], ['items:slots'], $provenance, 9_000);
        }
        $conflicts = [];
        foreach ($slotItems as $slot => $items) {
            if (count($items) !== count(array_unique($items)) || count(array_unique($items)) > 1) {
                $conflicts[] = $slot;
            }
        }
        if ($conflicts !== []) {
            $findings[] = $this->finding($id, $ruleset, 'equipment.slot.conflict', FindingSeverity::Warning, FindingCategory::DataQuality, 'Equipment slot assignments conflict', 'At least one normalized slot is assigned more than once; no assignment was chosen implicitly.', $conflicts, 'one item per slot', $conflicts, [], [], ['items:slots'], $provenance, 10_000);
        }
    }

    /**
     * @param  array<string, mixed>  $provenance
     * @param  list<Finding>  $findings
     */
    private function resistances(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, Poe1AnalysisRuleset $analysisRules, array $provenance, array &$findings): void
    {
        $stats = (new Poe1PlayerStatAliasRegistry($analysisRules->playerStatAliases))->canonicalize($build->summaryValues);
        foreach (['fire', 'cold', 'lightning'] as $element) {
            $currentKey = $element.'_resistance';
            $maximumKey = 'maximum_'.$element.'_resistance';
            $current = $this->integer($stats[$currentKey] ?? null);
            $maximum = $this->integer($stats[$maximumKey] ?? null);
            if ($current !== null && $maximum !== null && $current < $maximum) {
                $findings[] = $this->finding($id, $ruleset, 'defence.'.$element.'_resistance.below_reported_max', FindingSeverity::Warning, FindingCategory::Defence, ucfirst($element).' resistance is below the reported maximum', 'PoB reports the current resistance below its own reported maximum; no external cap was assumed.', $current, $maximum, [], [], [], ['player_stat:'.$currentKey, 'player_stat:'.$maximumKey], $provenance, 10_000);
            }
        }
        $chaos = $this->integer($stats['chaos_resistance'] ?? null);
        if ($chaos !== null && $chaos < 0) {
            $findings[] = $this->finding($id, $ruleset, 'defence.chaos_resistance.negative', FindingSeverity::Information, FindingCategory::Defence, 'Chaos resistance is negative', 'PoB reports a chaos resistance below zero; this is informational and does not assume a target cap.', $chaos, 0, [], [], [], ['player_stat:chaos_resistance'], $provenance, 10_000);
        }
    }

    /**
     * @param  array<string, mixed>  $provenance
     * @param  list<Finding>  $findings
     */
    private function mana(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, Poe1AnalysisRuleset $analysisRules, array $provenance, array &$findings): void
    {
        $stats = (new Poe1PlayerStatAliasRegistry($analysisRules->playerStatAliases))->canonicalize($build->summaryValues);
        $unreserved = $this->integer($stats['mana_unreserved'] ?? null);
        if ($unreserved !== null && $unreserved < 0) {
            $findings[] = $this->finding($id, $ruleset, 'resources.mana.unreserved_negative', FindingSeverity::Warning, FindingCategory::Resources, 'Unreserved mana is negative', 'The reported unreserved mana cannot be negative in a valid normalized reservation state.', $unreserved, 0, [], [], [], ['player_stat:mana_unreserved'], $provenance, 10_000);
        }
        $reserved = $this->integer($stats['mana_reserved'] ?? null);
        $total = $this->integer($stats['mana_total'] ?? null);
        if (($reserved !== null && $reserved < 0) || ($reserved !== null && $total !== null && $reserved > $total)) {
            $findings[] = $this->finding($id, $ruleset, 'resources.mana.reservation_invalid', FindingSeverity::Warning, FindingCategory::Resources, 'Mana reservation is invalid', 'PoB reports reserved mana outside the valid zero-to-total range.', ['reserved' => $reserved, 'total' => $total], ['minimum' => 0, 'maximum' => $total], [], [], [], ['player_stat:mana_reserved', 'player_stat:mana_total'], $provenance, 10_000);
        }
    }

    /**
     * @param  array<string, mixed>  $provenance
     * @param  list<Finding>  $findings
     */
    private function skills(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, Poe1AnalysisRuleset $analysisRules, array $provenance, array &$findings): void
    {
        $disabled = [];
        $mainGroups = [];
        $hasSkillGroups = $build->skills !== [];
        foreach ($build->skills as $groupIndex => $group) {
            $gems = is_array($group['gems'] ?? null) ? $group['gems'] : [];
            foreach ($gems as $gemIndex => $gem) {
                if (is_array($gem) && ($gem['enabled'] ?? null) === false) {
                    $disabled[] = is_string($gem['id'] ?? null) ? $gem['id'] : 'skill-group-'.($groupIndex + 1).'-gem-'.($gemIndex + 1);
                }
            }
            $mainIndex = $this->integer($group['main_active_gem_index'] ?? null);
            if (($group['enabled'] ?? null) === true && $mainIndex !== null && $mainIndex >= 1 && isset($gems[$mainIndex - 1]) && is_array($gems[$mainIndex - 1]) && ($gems[$mainIndex - 1]['enabled'] ?? null) === true) {
                $mainGroups[] = ['group' => $group, 'gem' => $gems[$mainIndex - 1], 'enabled_links' => count(array_filter($gems, static fn (mixed $gem): bool => is_array($gem) && ($gem['enabled'] ?? null) === true))];
            }
        }
        sort($disabled, SORT_STRING);
        if ($disabled !== []) {
            $findings[] = $this->finding($id, $ruleset, 'skills.gem.disabled', FindingSeverity::Information, FindingCategory::Skills, 'Disabled gems are present', 'One or more gems are explicitly disabled in the normalized PoB.', count($disabled), 0, [], $disabled, [], ['skills:gems:enabled'], $provenance, 10_000);
        }
        if ($hasSkillGroups && count($mainGroups) !== 1) {
            $findings[] = $this->finding($id, $ruleset, 'skills.main.unknown', FindingSeverity::Information, FindingCategory::Skills, 'Main skill could not be identified deterministically', 'The normalized build does not contain exactly one enabled skill group with an enabled main active gem. No damage or support assumptions were made.', count($mainGroups), 1, [], [], [], ['skills:main_active_gem_index', 'skills:gems:enabled'], $provenance, 10_000);
        }
        if (count($mainGroups) === 1 && $mainGroups[0]['enabled_links'] < $analysisRules->minimumMainSkillLinks) {
            $gemId = is_string($mainGroups[0]['gem']['id'] ?? null) ? $mainGroups[0]['gem']['id'] : 'unknown-main-gem';
            $findings[] = $this->finding($id, $ruleset, 'skills.main.insufficient_links', FindingSeverity::Warning, FindingCategory::Skills, 'Main active skill has fewer than the reviewed link count', 'PoB explicitly identifies one main active gem and its enabled group is below the versioned Lootwright review threshold.', $mainGroups[0]['enabled_links'], $analysisRules->minimumMainSkillLinks, [], [$gemId], [], ['skills:main_active_gem_index', 'skills:gems:enabled'], $provenance, 9_500);
        }
    }

    /**
     * @param  array<int|string, true>  $known
     * @param  array<string, mixed>  $provenance
     * @param  list<Finding>  $findings
     */
    private function passives(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, array $known, array $provenance, array &$findings): void
    {
        $unknown = [];
        foreach ($build->passiveNodeIds as $nodeId) {
            $raw = str_starts_with($nodeId, 'poe1.pob.node.') ? substr($nodeId, 14) : $nodeId;
            if (! isset($known[$raw])) {
                $unknown[] = $nodeId;
            }
        }
        $unknown = array_values(array_unique($unknown));
        sort($unknown, SORT_STRING);
        if ($unknown !== []) {
            $findings[] = $this->finding($id, $ruleset, 'passive_tree.node.unknown', FindingSeverity::Warning, FindingCategory::PassiveTree, 'Passive nodes are absent from the active snapshot', 'One or more PoB passive node IDs do not exist in the active immutable GGG passive-tree snapshot.', $unknown, 'node present in active snapshot', [], [], $unknown, ['passive_tree:node_ids', 'ruleset:passive_tree_snapshot'], $provenance, 10_000);
        }
    }

    /**
     * @param  array<string,mixed>  $provenance
     * @param  list<Finding>  &$findings
     */
    private function defensiveProfile(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, Poe1AnalysisRuleset $rules, array $provenance, array &$findings, ?string $contentGoal = null): void
    {
        $stats = (new Poe1PlayerStatAliasRegistry($rules->playerStatAliases))->canonicalize($build->summaryValues);
        // The production workflow carries the user-selected goal in the
        // immutable AnalysisContext intent. Direct callers may still provide
        // a legacy build configuration, so retain that compatibility fallback.
        $goal = $contentGoal ?? (string) ($build->configuration['content_goal'] ?? 'progression');
        $profile = $rules->contentProfiles[$goal] ?? $rules->contentProfiles['progression'];
        $life = $this->integer($build->life ?? $stats['life'] ?? null);
        $es = $this->integer($build->energyShield ?? $stats['energy_shield'] ?? null);
        $checks = [
            ['life', $life, (int) ($profile['life'] ?? 0), 'defence.life.below_content_profile', 'Life is below the selected content profile'],
            ['energy shield', $es, (int) ($profile['energy_shield'] ?? 0), 'defence.energy_shield.below_content_profile', 'Energy shield is below the selected content profile'],
        ];
        foreach ($checks as [$label, $value, $minimum, $code, $title]) {
            $ci = $this->hasKeystone($build, 'Chaos Inoculation');
            $lifeExempt = $label === 'life' && $ci;
            if ($value !== null && $minimum > 0 && $value < $minimum && ! $lifeExempt) {
                $findings[] = $this->finding($id, $ruleset, $code, FindingSeverity::Warning, FindingCategory::Defence, $title, 'The reported defensive pool is below the versioned threshold for the requested content profile.', $value, $minimum, [], [], [], ['player_stat:'.$label, 'content_profile:'.$goal], $provenance, 9_000);
            }
        }
        foreach ([
            ['armour', $build->armour ?? $stats['armour'] ?? null, 5000, 'defence.armour.low'],
            ['evasion', $build->evasion ?? $stats['evasion'] ?? null, 5000, 'defence.evasion.low'],
            ['block', $stats['block_chance'] ?? null, 20, 'defence.block.low'],
            ['spell block', $stats['spell_block_chance'] ?? null, 20, 'defence.spell_block.low'],
            ['suppression', $stats['spell_suppression'] ?? null, 50, 'defence.suppression.low'],
        ] as [$label, $value, $minimum, $code]) {
            $numeric = $this->integer($value);
            if ($numeric !== null && $numeric < $minimum) {
                $findings[] = $this->finding($id, $ruleset, $code, FindingSeverity::Opportunity, FindingCategory::Defence, ucfirst($label).' is low', 'PoB reports a value below the conservative profile baseline; the exact target depends on content and build mechanics.', $numeric, $minimum, [], [], [], ['player_stat:'.$label], $provenance, 8_000);
            }
        }
    }

    /**
     * @param  array<string,mixed>  $provenance
     * @param  list<Finding>  &$findings
     */
    private function recoveryAndOffence(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, array $provenance, array &$findings): void
    {
        $stats = (new Poe1PlayerStatAliasRegistry)->canonicalize($build->summaryValues);
        $recoveryKeys = ['LifeRegen', 'LifeRegenRate', 'LifeLeechRate', 'EnergyShieldRegen'];
        $recoveryEvidence = array_filter($recoveryKeys, static fn (string $key): bool => array_key_exists($key, $build->summaryValues));
        $hasRecovery = array_filter($recoveryEvidence, static fn (string $key): bool => (int) $build->summaryValues[$key] > 0) !== [];
        if ($recoveryEvidence !== [] && ! $hasRecovery && $build->life !== null && $this->integer($build->life) !== null && $this->integer($build->life) > 0) {
            $findings[] = $this->finding($id, $ruleset, 'recovery.life.missing', FindingSeverity::Warning, FindingCategory::Recovery, 'No life recovery was reported', 'The normalized summary contains no positive regeneration, leech, or energy-shield recovery signal.', null, 'one recovery source', [], [], [], ['summary_values:recovery'], $provenance, 7_500);
        }
        if ($this->hasKeystone($build, 'Resolute Technique') && ($stats['critical_strike_chance'] ?? null) !== null) {
            $findings[] = $this->finding($id, $ruleset, 'offence.crit.resolute_technique_conflict', FindingSeverity::Warning, FindingCategory::KeystoneConflicts, 'Resolute Technique conflicts with critical-strike scaling', 'The active keystone prevents critical strikes; critical scaling recommendations are suppressed.', true, false, [], [], [], ['keystone:resolute_technique', 'player_stat:CriticalStrikeChance'], $provenance, 10_000);
        }
    }

    /**
     * Evaluate only explicitly reported requirements and costs. Missing
     * fields remain unknown; the analyzer never invents gem/item metadata.
     *
     * @param  array<string,mixed>  $provenance
     * @param  list<Finding>  &$findings
     */
    private function attributesAndSustainability(CanonicalImportedBuild $build, AnalysisId $id, RulesetIdentity $ruleset, Poe1AnalysisRuleset $rules, array $provenance, array &$findings): void
    {
        $stats = (new Poe1PlayerStatAliasRegistry($rules->playerStatAliases))->canonicalize([...$build->summaryValues, ...$build->attributes]);
        foreach ([['strength', 'strength_requirement'], ['dexterity', 'dexterity_requirement'], ['intelligence', 'intelligence_requirement']] as [$attribute, $requirement]) {
            $value = $this->integer($stats[$attribute] ?? null);
            $needed = $this->integer($stats[$requirement] ?? null);
            if ($value !== null && $needed !== null && $value < $needed) {
                $findings[] = $this->finding($id, $ruleset, 'attributes.requirement.missing', FindingSeverity::Critical, FindingCategory::Attributes, 'Attribute requirement is not met', 'The reported attribute total is below an explicit requirement in the normalized build.', ['attribute' => $attribute, 'value' => $value], ['minimum' => $needed], [], [], [], ['player_stat:'.$attribute, 'player_stat:'.$requirement], $provenance, 10_000);
            }
        }
        $cost = $this->integer($stats['mana_cost'] ?? null);
        $available = $this->integer($stats['mana_unreserved'] ?? null);
        if ($cost !== null && $available !== null && $cost > $available) {
            $findings[] = $this->finding($id, $ruleset, 'resources.mana.cost_unsustainable', FindingSeverity::Warning, FindingCategory::Resources, 'Main skill mana cost exceeds unreserved mana', 'The explicit reported mana cost cannot be paid from the available unreserved mana pool.', $cost, $available, [], [], [], ['player_stat:mana_cost', 'player_stat:mana_unreserved'], $provenance, 10_000);
        }
    }

    private function hasKeystone(CanonicalImportedBuild $build, string $name): bool
    {
        $needle = preg_replace('/[^a-z0-9]+/', '_', strtolower($name)) ?? strtolower($name);
        foreach ($build->keystones as $keystone) {
            $value = strtolower(trim((string) $keystone));
            $value = preg_replace('/^poe1\\.pob\\.(?:keystone|node)\\./', '', $value) ?? $value;
            $value = preg_replace('/^keystone:/', '', $value) ?? $value;
            $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
            if ($value === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $slots
     * @param  list<string>  $gems
     * @param  list<string>  $nodes
     * @param  list<string>  $evidence
     * @param  array<string, mixed>  $provenance
     */
    private function finding(AnalysisId $id, RulesetIdentity $ruleset, string $code, FindingSeverity $severity, FindingCategory $category, string $title, string $explanation, mixed $observed, mixed $expected, array $slots, array $gems, array $nodes, array $evidence, array $provenance, int $confidence): Finding
    {
        $result = Finding::deterministic($id, $ruleset, $code, $severity, $category, $title, $explanation, $observed, $expected, $slots, $gems, $nodes, $evidence, $provenance, $confidence);
        if ($result->isFailure() || ! $result->value() instanceof Finding) {
            throw new RuntimeException('A deterministic finding failed domain validation.');
        }

        return $result->value();
    }

    /** @param array<string, list<string>> $requiredSlotAliases */
    private function normalizeSlot(string $slot, array $requiredSlotAliases): string
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', ' ', trim($slot)));
        $normalized = trim($normalized);
        foreach ($requiredSlotAliases as $canonical => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $canonical;
            }
        }

        return str_replace(' ', '_', $normalized);
    }

    private function integer(mixed $value): ?int
    {
        return is_int($value) ? $value : (is_string($value) && preg_match('/^-?[0-9]+$/D', $value) === 1 ? (int) $value : null);
    }
}
