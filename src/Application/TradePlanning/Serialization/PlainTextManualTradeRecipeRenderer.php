<?php

namespace Lootwright\Application\TradePlanning\Serialization;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\RecipeFilter;
use Lootwright\Application\TradePlanning\DTO\RecipeVariant;

final class PlainTextManualTradeRecipeRenderer
{
    private function __construct() {}

    public static function render(ManualTradeRecipe $recipe): string
    {
        $lines = [
            'Lootwright Manual Trade Recipe',
            'Game: '.$recipe->gameEdition,
            'Realm: '.$recipe->platformRealm,
            'League: '.($recipe->league ?? 'not specified'),
            'Slot: '.$recipe->slot,
            'Budget: '.self::budget($recipe->budget),
        ];

        if ($recipe->itemTarget !== null) {
            $lines[] = 'Category: '.$recipe->itemTarget->exactCategoryLabel;

            if ($recipe->itemTarget->exactBaseFamilyLabel !== null) {
                $lines[] = 'Base family: '.$recipe->itemTarget->exactBaseFamilyLabel;
            }
        }

        $lines[] = '';
        array_push($lines, ...self::variant($recipe->strict));
        $lines[] = '';
        array_push($lines, ...self::variant($recipe->broadFallback));

        if ($recipe->constraints !== [] || $recipe->affixPreference !== null) {
            $lines[] = '';
            $lines[] = 'Applicable constraints:';

            foreach ($recipe->constraints as $constraint) {
                $lines[] = '- '.$constraint->exactLabel;
            }

            if ($recipe->affixPreference !== null) {
                $lines[] = '- '.$recipe->affixPreference->exactLabel;
            }
        }

        if ($recipe->dependencies !== []) {
            $lines[] = '';
            $lines[] = 'Other-slot dependencies:';

            foreach ($recipe->dependencies as $dependency) {
                $lines[] = '- '.$dependency->slot.': '.$dependency->reason;
            }
        }

        if ($recipe->unresolvedRequirements !== []) {
            $lines[] = '';
            $lines[] = 'Unresolved requirements — clarification required:';

            foreach ($recipe->unresolvedRequirements as $requirement) {
                $lines[] = '- '.$requirement->canonicalKey.': '.$requirement->clarificationQuestion;
            }
        }

        $lines[] = '';
        $lines[] = 'Ruleset: '.$recipe->ruleset['id'].' '.$recipe->ruleset['version'].' ('.$recipe->ruleset['checksum_sha256'].')';
        $lines[] = 'Source: '.$recipe->ruleset['source_id'].' '.$recipe->ruleset['source_version'];
        $lines[] = 'Confidence: '.$recipe->confidenceBasisPoints.' basis points';
        $lines[] = 'Apply these values manually on the official Trade homepage.';

        return implode("\n", $lines);
    }

    /** @return list<string> */
    private static function variant(RecipeVariant $variant): array
    {
        $lines = [$variant->name.':'];

        foreach ([
            'Required filters' => $variant->required,
            'Weighted optional filters' => $variant->weighted,
            'Excluded or conflicting modifiers' => $variant->excluded,
        ] as $heading => $filters) {
            $lines[] = $heading.':';

            if ($filters === []) {
                $lines[] = '- none';

                continue;
            }

            foreach ($filters as $filter) {
                $lines[] = self::filter($filter);
            }
        }

        return $lines;
    }

    private static function filter(RecipeFilter $filter): string
    {
        $values = [];

        if ($filter->range?->minimum !== null) {
            $values[] = 'minimum '.$filter->range->minimum;
        }

        if ($filter->range?->maximum !== null) {
            $values[] = 'maximum '.$filter->range->maximum;
        }

        if ($filter->weight !== null) {
            $values[] = 'weight '.$filter->weight;
        }

        $suffix = $values === [] ? '' : ' — '.implode(', ', $values);

        return '- '.$filter->exactLabel.$suffix.' — '.$filter->reason;
    }

    /** @param array{currency: string, amount: string}|null $budget */
    private static function budget(?array $budget): string
    {
        return $budget === null ? 'not specified' : $budget['amount'].' '.$budget['currency'];
    }
}
