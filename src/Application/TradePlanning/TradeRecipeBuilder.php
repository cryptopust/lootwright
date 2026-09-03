<?php

namespace Lootwright\Application\TradePlanning;

use InvalidArgumentException;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\Recommendations\UpgradeCandidate;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\TradePlanning\ConstraintCompiler;
use Lootwright\Domain\TradePlanning\RecipeValidator;
use Lootwright\Domain\TradePlanning\TradeRecipe;
use Lootwright\Domain\TradePlanning\TradeVocabulary;

/** Converts approved deterministic candidates into manual, descriptive recipes. */
final readonly class TradeRecipeBuilder
{
    public function __construct(
        private ModifierMatcher $matcher,
        private ConstraintCompiler $compiler = new ConstraintCompiler,
        private RecipeValidator $validator = new RecipeValidator,
    ) {}

    public function build(UpgradeCandidate $candidate, BuildSnapshot $build, GameRuleset $ruleset, TradeVocabulary $vocabulary): TradeRecipe
    {
        $edition = $candidate->gameEdition;
        if ($edition !== $build->scope->edition || $edition !== $ruleset->identity->edition || $edition !== $vocabulary->edition()) {
            throw new InvalidArgumentException('Trade recipe inputs must share one game edition.');
        }
        if (! $candidate->ruleset->equals(new RulesetReference($edition, $ruleset->identity->id->value, $ruleset->identity->version->value, $ruleset->identity->checksumSha256))
            || ! $vocabulary->ruleset()->equals(new RulesetReference($edition, $ruleset->identity->id->value, $ruleset->identity->version->value, $ruleset->identity->checksumSha256))
            || ! $build->patch->equals($ruleset->gameVersion->patch)
            || ! $build->parserVersion->equals($ruleset->identity->parserVersion)
            || ($build->league === null) !== ($ruleset->identity->league === null)
            || ($build->league !== null
                && $ruleset->identity->league !== null
                && ! $build->league->equals($ruleset->identity->league))
            || $ruleset->compatibilityStatus !== RulesetCompatibilityStatus::Compatible
            || ! $ruleset->approvedForProduction()
            || ! $vocabulary->enabled()
        ) {
            throw new InvalidArgumentException('Trade recipe requires an exact compatible ruleset and vocabulary.');
        }

        $slot = $candidate->targetSlot ?? 'unspecified';
        $required = [];
        $optional = [];
        $excluded = [];
        $unsupported = [];
        $minimumValues = [];
        $weights = [];

        $positiveIds = [];
        $excludedIds = [];
        $seenIds = [];
        foreach ($candidate->tradeRequirements as $requirement) {
            $id = (string) $requirement['modifier_id'];
            if (isset($seenIds[$id])) {
                throw new InvalidArgumentException('A canonical modifier can appear only once in a Trade recipe.');
            }
            $seenIds[$id] = true;
            $entry = $this->matcher->resolve($vocabulary, $ruleset->identity, $id);
            if ($entry === null || $entry->edition !== $edition) {
                $unsupported[] = ['modifier_id' => $id, 'reason' => 'No exact approved edition-scoped vocabulary mapping exists.'];

                continue;
            }
            $filter = ['canonical_modifier_id' => $id, 'label' => $entry->label, 'provenance' => $entry->provenance];
            if (isset($requirement['minimum']) && is_string($requirement['minimum'])) {
                $filter['minimum'] = $requirement['minimum'];
                $minimumValues[$id] = $requirement['minimum'];
            }
            if (isset($requirement['weight']) && is_int($requirement['weight'])) {
                $filter['weight'] = $requirement['weight'];
                $weights[$id] = $requirement['weight'];
            }
            $mode = (string) $requirement['mode'];
            match ($mode) {
                'optional' => $optional[] = $filter,
                'excluded' => $excluded[] = $filter,
                default => $required[] = $filter,
            };
            if ($mode === 'excluded') {
                $excludedIds[$id] = true;
            } else {
                $positiveIds[$id] = $entry;
            }
        }

        foreach ($positiveIds as $id => $entry) {
            foreach ($entry->conflicts as $conflict) {
                if (isset($positiveIds[$conflict])) {
                    throw new InvalidArgumentException('The approved vocabulary marks positive recipe modifiers as conflicting.');
                }
                if (! isset($excludedIds[$conflict])) {
                    $conflictEntry = $this->matcher->resolve($vocabulary, $ruleset->identity, $conflict);
                    if ($conflictEntry === null) {
                        $unsupported[] = ['modifier_id' => $conflict, 'reason' => 'A declared conflicting modifier has no approved vocabulary mapping.'];
                    } else {
                        $excluded[] = ['canonical_modifier_id' => $conflict, 'label' => $conflictEntry->label, 'provenance' => $conflictEntry->provenance];
                        $excludedIds[$conflict] = true;
                    }
                }
            }
        }

        if ($candidate->tradeRequirements === []) {
            $unsupported[] = ['candidate' => $candidate->id, 'reason' => 'The candidate contains no structured, approved Trade requirements.'];
        }

        $dependencies = array_map(static fn (string $dependent): array => [
            'slot' => $dependent,
            'reason' => 'Changing this slot may alter requirements carried by another proposed slot; review the deterministic finding before applying both recipes.',
            'affected_candidate' => $candidate->id,
            'affected_findings' => $candidate->affectedFindings,
        ], $candidate->dependentSlots);
        $strict = $this->compiler->compile('Required', $required)."\n\n".$this->compiler->compile('Optional', $optional)."\n\n".$this->compiler->compile('Excluded', $excluded);
        $broad = $this->compiler->compile('Required', [])."\n\n".$this->compiler->compile('Optional', [...$optional, ...$this->relax($required)])."\n\n".$this->compiler->compile('Excluded', $excluded);
        $reference = new RulesetReference($edition, $ruleset->identity->id->value, $ruleset->identity->version->value, $ruleset->identity->checksumSha256);
        $recipe = new TradeRecipe(
            $edition,
            $reference,
            $slot,
            $vocabulary->itemClass($slot),
            [],
            null,
            null,
            null,
            $required,
            $optional,
            $excluded,
            $minimumValues,
            $weights,
            $dependencies,
            $broad,
            $strict,
            'Manual filters are derived only from deterministic findings and exact vocabulary entries. No listings, prices, IDs, or search URL are generated.',
            [
                'source_id' => $ruleset->identity->provenance->sourceId,
                'source_version' => $ruleset->identity->provenance->sourceVersion->value,
                'checksum_sha256' => $ruleset->identity->checksumSha256,
            ],
            $unsupported,
        );
        $this->validator->validate($recipe);

        return $recipe;
    }

    /**
     * @param  array<int,array<string,mixed>>  $filters
     * @return list<array<string,mixed>>
     */
    private function relax(array $filters): array
    {
        return array_values(array_map(static function (array $filter): array {
            if (isset($filter['minimum'])) {
                unset($filter['minimum']);
            }

            return $filter;
        }, $filters));
    }
}
