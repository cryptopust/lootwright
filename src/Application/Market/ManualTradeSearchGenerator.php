<?php

namespace Lootwright\Application\Market;

use Lootwright\Application\Market\Ports\TradeSearchGenerator;
use Lootwright\Domain\TradePlanning\TradeRecipe;

/** Compiles all UX modes from the already validated manual recipe. */
final readonly class ManualTradeSearchGenerator implements TradeSearchGenerator
{
    public function generate(TradeRecipe $recipe, string $league, TradeSearchMode $mode): TradeSearchPlan
    {
        $filters = match ($mode) {
            TradeSearchMode::Strict => $recipe->requiredModifiers,
            TradeSearchMode::Broad => [...$recipe->optionalModifiers, ...$recipe->requiredModifiers],
            TradeSearchMode::Budget => $recipe->requiredModifiers,
            TradeSearchMode::Alternative => $recipe->optionalModifiers,
        };
        $text = $mode->value.' search for '.$recipe->slot."\n".$this->render($filters);
        if ($mode === TradeSearchMode::Budget) {
            $text .= "\nSort by lowest price; keep the deterministic minimums.";
        }
        if ($mode === TradeSearchMode::Alternative) {
            $text .= "\nAlternative path: review dependencies and excluded modifiers before applying.";
        }

        return new TradeSearchPlan($recipe->gameEdition, $league, $mode, array_values($filters), $text);
    }

    /** @param list<array<string,mixed>> $filters */
    private function render(array $filters): string
    {
        if ($filters === []) {
            return '- no supported filters';
        }

        return implode("\n", array_map(static fn (array $filter): string => '- '.(string) ($filter['label'] ?? $filter['canonical_modifier_id']).(isset($filter['minimum']) ? ' (min '.(string) $filter['minimum'].')' : ''), $filters));
    }
}
