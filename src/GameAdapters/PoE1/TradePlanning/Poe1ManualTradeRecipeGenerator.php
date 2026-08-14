<?php

namespace Lootwright\GameAdapters\PoE1\TradePlanning;

use Lootwright\Application\TradePlanning\DTO\ApprovedTradeVocabulary;
use Lootwright\Application\TradePlanning\DTO\ItemConstraintDefinition;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\DTO\NumericRange;
use Lootwright\Application\TradePlanning\DTO\RecipeDependency;
use Lootwright\Application\TradePlanning\DTO\RecipeFilter;
use Lootwright\Application\TradePlanning\DTO\RecipeFilterMode;
use Lootwright\Application\TradePlanning\DTO\RecipeVariant;
use Lootwright\Application\TradePlanning\DTO\ResolvedItemConstraint;
use Lootwright\Application\TradePlanning\DTO\ResolvedItemTarget;
use Lootwright\Application\TradePlanning\DTO\SlotFilterIntent;
use Lootwright\Application\TradePlanning\DTO\TradeFilterDefinition;
use Lootwright\Application\TradePlanning\DTO\UnresolvedRequirement;
use Lootwright\Application\TradePlanning\Exception\ManualRecipeGenerationFailed;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Shared\Game\GameEdition;

final class Poe1ManualTradeRecipeGenerator
{
    public function generate(ManualTradeRecipeRequest $request): ManualTradeRecipe
    {
        $this->guardRequest($request);
        $unresolved = [];
        $confidence = $request->plan->confidence->basisPoints;
        $target = $this->target($request, $unresolved, $confidence);
        $resolved = $this->resolveFilters($request, $unresolved, $confidence);
        $strict = $this->variant('Stricter recipe', $resolved, true, $request->vocabulary, $unresolved, $confidence);
        $broad = $this->variant('Broad fallback recipe', $resolved, false, $request->vocabulary, $unresolved, $confidence);
        $constraints = $this->constraints($request, $unresolved, $confidence);
        $affix = $this->affixPreference($request, $unresolved, $confidence);
        $dependencies = $this->dependencies($request);
        $ruleset = $request->ruleset;

        return new ManualTradeRecipe(
            $request->scope->edition->value,
            $request->scope->realm->value,
            $request->league?->value,
            $request->plan->slot->value,
            $request->budget?->jsonSerialize(),
            $target,
            $broad,
            $strict,
            $constraints,
            $affix,
            $dependencies,
            array_values($unresolved),
            [
                'id' => $ruleset->id->value,
                'version' => $ruleset->version->value,
                'checksum_sha256' => $ruleset->checksumSha256,
                'patch' => $ruleset->patch->value,
                'league' => $ruleset->league?->value,
                'parser_version' => $ruleset->parserVersion->value,
                'source_id' => $ruleset->provenance->sourceId,
                'source_version' => $ruleset->provenance->sourceVersion->value,
            ],
            $confidence,
            'https://www.pathofexile.com/trade',
            'Open the official Path of Exile Trade homepage',
        );
    }

    private function guardRequest(ManualTradeRecipeRequest $request): void
    {
        $edition = GameEdition::Poe1;
        $ruleset = $request->ruleset;
        $plan = $request->plan;

        if ($request->scope->edition !== $edition
            || $ruleset->edition !== $edition
            || $plan->recommendation->edition !== $edition
            || ! $plan->slot->belongsTo($edition)
            || ! $request->vocabulary->ruleset->equals($ruleset)
            || $ruleset->provenance->permission !== PermissionStatus::Allowed
            || $ruleset->provenance->commercialUse !== CommercialUseStatus::Allowed
            || ($request->league === null) !== ($ruleset->league === null)
            || ($request->league !== null
                && $ruleset->league !== null
                && ! $request->league->equals($ruleset->league))
        ) {
            throw new ManualRecipeGenerationFailed(
                'recipe_context_mismatch',
                'Recipe scope, slot, recommendation, vocabulary, league, and ruleset must match exactly.',
            );
        }

        if ($plan->filters === []) {
            throw new ManualRecipeGenerationFailed('empty_filter_plan', 'A slot recipe requires at least one deterministic filter intent.');
        }

        $recommendationFindings = [];

        foreach ($plan->recommendation->findings as $finding) {
            $recommendationFindings[$finding->code] = $finding;
        }

        foreach ($plan->filters as $intent) {
            $ownedFinding = $recommendationFindings[$intent->finding->code] ?? null;

            if (! $ownedFinding instanceof Finding
                || ! $ownedFinding->analysisId->equals($intent->finding->analysisId)
                || ! $intent->modifierId->belongsTo($edition)
                || ! $intent->finding->rule->rulesetId->equals($ruleset->id)
                || ! $intent->finding->rule->rulesetVersion->equals($ruleset->version)
            ) {
                throw new ManualRecipeGenerationFailed(
                    'finding_trace_mismatch',
                    'Every filter intent must trace to a finding owned by the recommendation.',
                );
            }

            $this->guardIntent($intent);
        }

        foreach ($plan->dependencies as $dependency) {
            $ownedFinding = $recommendationFindings[$dependency->finding->code] ?? null;

            if (! $dependency->slot->belongsTo($edition)
                || ! $ownedFinding instanceof Finding
                || ! $ownedFinding->analysisId->equals($dependency->finding->analysisId)
                || $dependency->slot->equals($plan->slot)
                || ! $this->safeText($dependency->reason, 500)
            ) {
                throw new ManualRecipeGenerationFailed('invalid_slot_dependency', 'Recipe dependencies require another edition-matched slot and finding.');
            }
        }
    }

    private function guardIntent(SlotFilterIntent $intent): void
    {
        $this->guardMode($intent->strictMode, $intent->strictRange, $intent->strictWeight);
        $this->guardMode($intent->broadMode, $intent->broadRange, $intent->broadWeight);

        if (! $this->safeText($intent->reason, 500)
            || ! $this->isRelaxation($intent)
        ) {
            throw new ManualRecipeGenerationFailed(
                'invalid_budget_relaxation',
                'The broad recipe must only relax the stricter filter mode, range, or weight.',
            );
        }
    }

    private function guardMode(RecipeFilterMode $mode, ?NumericRange $range, ?int $weight): void
    {
        $valid = match ($mode) {
            RecipeFilterMode::Required => $range !== null && $weight === null,
            RecipeFilterMode::Weighted => $range !== null && $weight !== null && $weight >= 1 && $weight <= 100,
            RecipeFilterMode::Excluded => $weight === null,
            RecipeFilterMode::Omitted => $range === null && $weight === null,
        };

        if (! $valid) {
            throw new ManualRecipeGenerationFailed('invalid_filter_mode', 'Filter ranges and weights must match their declared mode.');
        }
    }

    private function isRelaxation(SlotFilterIntent $intent): bool
    {
        $allowed = match ($intent->strictMode) {
            RecipeFilterMode::Required => [RecipeFilterMode::Required, RecipeFilterMode::Weighted, RecipeFilterMode::Omitted],
            RecipeFilterMode::Weighted => [RecipeFilterMode::Weighted, RecipeFilterMode::Omitted],
            RecipeFilterMode::Excluded => [RecipeFilterMode::Excluded, RecipeFilterMode::Omitted],
            RecipeFilterMode::Omitted => [RecipeFilterMode::Omitted],
        };

        if (! in_array($intent->broadMode, $allowed, true)) {
            return false;
        }

        if ($intent->broadMode === RecipeFilterMode::Omitted) {
            return true;
        }

        if ($intent->strictRange !== null
            && ($intent->broadRange === null || ! $intent->broadRange->contains($intent->strictRange))
        ) {
            return false;
        }

        return $intent->strictMode !== RecipeFilterMode::Weighted
            || $intent->broadWeight === null
            || $intent->strictWeight === null
            || $intent->broadWeight <= $intent->strictWeight;
    }

    /**
     * @param  array<string, UnresolvedRequirement>  $unresolved
     */
    private function target(ManualTradeRecipeRequest $request, array &$unresolved, int &$confidence): ?ResolvedItemTarget
    {
        $code = $request->plan->itemTargetCode;

        if ($code === null) {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $code) !== 1) {
            throw new ManualRecipeGenerationFailed('invalid_item_target', 'Item target codes must be canonical vocabulary keys.');
        }

        $target = $request->vocabulary->itemTarget($code);

        if ($target === null || ! $this->safeLabel($target->exactCategoryLabel) || ($target->exactBaseFamilyLabel !== null && ! $this->safeLabel($target->exactBaseFamilyLabel))) {
            $finding = $request->plan->recommendation->findings[0];
            $unresolved['target:'.$code] = $this->unresolved('item_target', $code, $finding);

            return null;
        }

        $confidence = min($confidence, $target->confidence->basisPoints);
        $finding = $request->plan->recommendation->findings[0];

        return new ResolvedItemTarget(
            $target->code,
            $target->exactCategoryLabel,
            $target->exactBaseFamilyLabel,
            $target->rule->ruleKey,
            $target->confidence->basisPoints,
            $finding->code,
            $finding->trace,
        );
    }

    /**
     * @param  array<string, UnresolvedRequirement>  $unresolved
     * @return list<array{intent: SlotFilterIntent, definition: TradeFilterDefinition}>
     */
    private function resolveFilters(ManualTradeRecipeRequest $request, array &$unresolved, int &$confidence): array
    {
        $resolved = [];
        $seen = [];

        foreach ($request->plan->filters as $intent) {
            $id = $intent->modifierId->value;

            if (isset($seen[$id])) {
                throw new ManualRecipeGenerationFailed('duplicate_filter', 'A canonical modifier can appear only once in a slot plan.');
            }

            $seen[$id] = true;
            $definition = $request->vocabulary->filter($id);

            if ($definition === null || ! $this->safeLabel($definition->exactLabel)) {
                $unresolved['filter:'.$id] = $this->unresolved('modifier', $id, $intent->finding);

                continue;
            }

            $confidence = min($confidence, $definition->confidence->basisPoints);
            $resolved[] = ['intent' => $intent, 'definition' => $definition];
        }

        return $resolved;
    }

    /**
     * @param  list<array{intent: SlotFilterIntent, definition: TradeFilterDefinition}>  $resolved
     * @param  array<string, UnresolvedRequirement>  $unresolved
     */
    private function variant(
        string $name,
        array $resolved,
        bool $strict,
        ApprovedTradeVocabulary $vocabulary,
        array &$unresolved,
        int &$confidence,
    ): RecipeVariant {
        $required = [];
        $weighted = [];
        $excluded = [];
        $positive = [];
        $excludedIds = [];

        foreach ($resolved as $entry) {
            $intent = $entry['intent'];
            $definition = $entry['definition'];
            $mode = $strict ? $intent->strictMode : $intent->broadMode;

            if ($mode === RecipeFilterMode::Omitted) {
                continue;
            }

            $filter = $this->filter($intent, $definition, $strict, $mode);

            match ($mode) {
                RecipeFilterMode::Required => $required[] = $filter,
                RecipeFilterMode::Weighted => $weighted[] = $filter,
                RecipeFilterMode::Excluded => $excluded[] = $filter,
            };

            if ($mode === RecipeFilterMode::Excluded) {
                $excludedIds[$definition->modifierId->value] = true;
            } else {
                $positive[$definition->modifierId->value] = $entry;
            }
        }

        foreach ($positive as $entry) {
            $definition = $entry['definition'];
            $intent = $entry['intent'];

            foreach ($definition->conflictingModifierIds as $conflictId) {
                if (isset($positive[$conflictId])) {
                    throw new ManualRecipeGenerationFailed(
                        'conflicting_filters',
                        'The approved vocabulary marks two positive filters as conflicting.',
                    );
                }

                if (isset($excludedIds[$conflictId])) {
                    continue;
                }

                $conflict = $vocabulary->filter($conflictId);

                if ($conflict === null || ! $this->safeLabel($conflict->exactLabel)) {
                    $unresolved['conflict:'.$conflictId] = $this->unresolved('conflicting_modifier', $conflictId, $intent->finding);

                    continue;
                }

                $excluded[] = new RecipeFilter(
                    $conflict->modifierId->value,
                    $conflict->exactLabel,
                    null,
                    null,
                    'Excluded because it conflicts with '.$definition->exactLabel.' in the approved ruleset.',
                    $intent->finding->code,
                    $intent->finding->trace,
                    $conflict->rule->ruleKey,
                    min($definition->confidence->basisPoints, $conflict->confidence->basisPoints),
                );
                $confidence = min($confidence, $conflict->confidence->basisPoints);
                $excludedIds[$conflictId] = true;
            }
        }

        return new RecipeVariant($name, $required, $weighted, $excluded);
    }

    private function filter(
        SlotFilterIntent $intent,
        TradeFilterDefinition $definition,
        bool $strict,
        RecipeFilterMode $mode,
    ): RecipeFilter {
        return new RecipeFilter(
            $definition->modifierId->value,
            $definition->exactLabel,
            $strict ? $intent->strictRange : $intent->broadRange,
            $mode === RecipeFilterMode::Weighted
                ? ($strict ? $intent->strictWeight : $intent->broadWeight)
                : null,
            $intent->reason,
            $intent->finding->code,
            $intent->finding->trace,
            $definition->rule->ruleKey,
            $definition->confidence->basisPoints,
        );
    }

    /**
     * @param  array<string, UnresolvedRequirement>  $unresolved
     * @return list<ResolvedItemConstraint>
     */
    private function constraints(ManualTradeRecipeRequest $request, array &$unresolved, int &$confidence): array
    {
        $constraints = [];
        $seen = [];
        $finding = $request->plan->recommendation->findings[0];

        foreach ($request->plan->constraintCodes as $code) {
            if (isset($seen[$code]) || preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $code) !== 1) {
                throw new ManualRecipeGenerationFailed('invalid_item_constraint', 'Constraint codes must be unique canonical vocabulary keys.');
            }

            $seen[$code] = true;
            $definition = $request->vocabulary->constraint($code);

            if ($definition === null || ! $this->safeLabel($definition->exactLabel)) {
                $unresolved['constraint:'.$code] = $this->unresolved('item_constraint', $code, $finding);

                continue;
            }

            $confidence = min($confidence, $definition->confidence->basisPoints);
            $constraints[] = $this->constraint($definition, $finding);
        }

        return $constraints;
    }

    /** @param array<string, UnresolvedRequirement> $unresolved */
    private function affixPreference(ManualTradeRecipeRequest $request, array &$unresolved, int &$confidence): ?ResolvedItemConstraint
    {
        $code = $request->plan->affixPreferenceCode;

        if ($code === null) {
            return null;
        }

        $finding = $request->plan->recommendation->findings[0];
        $definition = $request->vocabulary->constraint($code);

        if ($definition === null
            || ! in_array($code, ['affix.open_prefix', 'affix.open_suffix'], true)
            || ! $this->safeLabel($definition->exactLabel)
        ) {
            $unresolved['affix:'.$code] = $this->unresolved('affix_preference', $code, $finding);

            return null;
        }

        $confidence = min($confidence, $definition->confidence->basisPoints);

        return $this->constraint($definition, $finding);
    }

    private function constraint(ItemConstraintDefinition $definition, Finding $finding): ResolvedItemConstraint
    {
        return new ResolvedItemConstraint(
            $definition->code,
            $definition->exactLabel,
            $definition->rule->ruleKey,
            $definition->confidence->basisPoints,
            $finding->code,
            $finding->trace,
        );
    }

    /** @return list<RecipeDependency> */
    private function dependencies(ManualTradeRecipeRequest $request): array
    {
        $dependencies = [];

        foreach ($request->plan->dependencies as $dependency) {
            $dependencies[] = new RecipeDependency(
                $dependency->slot->value,
                $dependency->reason,
                $dependency->finding->code,
                $dependency->finding->trace,
            );
        }

        return $dependencies;
    }

    private function unresolved(string $kind, string $key, Finding $finding): UnresolvedRequirement
    {
        return new UnresolvedRequirement(
            $kind,
            $key,
            'The exact approved ruleset vocabulary does not prove this mapping.',
            'Which exact in-game filter label should represent '.$key.' for this patch?',
            $finding->code,
            $finding->trace,
        );
    }

    private function safeLabel(string $label): bool
    {
        return $this->safeText($label, 240);
    }

    private function safeText(string $text, int $maximum): bool
    {
        $text = trim($text);

        return $text !== ''
            && mb_strlen($text) <= $maximum
            && preg_match('#(?:https?://|/api/|[{}]|[\x00-\x08\x0B\x0C\x0E-\x1F])#i', $text) !== 1;
    }
}
