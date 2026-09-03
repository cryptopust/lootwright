<?php

namespace Lootwright\Domain\Recommendations;

final readonly class ConstraintEvaluator
{
    public function evaluate(UpgradeCandidate $candidate, UserConstraints $constraints): ConstraintEvaluation
    {
        foreach ($constraints->values as $constraint) {
            if ($constraint->strength !== ConstraintStrength::Hard) {
                continue;
            }
            $preservedItem = str_starts_with($constraint->key, 'preserve:item:') ? substr($constraint->key, strlen('preserve:item:')) : null;
            $explicitItemReplacement = $preservedItem !== null && in_array('item:'.$preservedItem.':replace', $candidate->expectedEffects, true);
            $knownMagebloodBeltReplacement = $preservedItem === 'mageblood' && in_array('slot:belt', $candidate->dependentSlots, true);
            if ($explicitItemReplacement || $knownMagebloodBeltReplacement) {
                return new ConstraintEvaluation(false, 'A preserved item occupies the dependent belt slot.');
            }
            if ($constraint->key === 'preserve:main_skill' && in_array('main_skill:replace', $candidate->expectedEffects, true)) {
                return new ConstraintEvaluation(false, 'The candidate replaces the preserved main skill.');
            }
            if ($constraint->key === 'avoid:passive_tree_rebuild' && in_array('passive_tree:rebuild', $candidate->expectedEffects, true)) {
                return new ConstraintEvaluation(false, 'The candidate requires a full passive-tree rebuild.');
            }
        }

        $adjustment = 0;
        foreach ($constraints->values as $constraint) {
            if ($constraint->strength === ConstraintStrength::Preference && str_contains(strtolower($candidate->title), strtolower($constraint->value))) {
                $adjustment += 500;
            }
        }

        return new ConstraintEvaluation(true, scoreAdjustment: $adjustment);
    }
}
