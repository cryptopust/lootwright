<?php

namespace Lootwright\Domain\TradePlanning;

use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Immutable manual recipe; it contains no Trade query or request representation. */
final readonly class TradeRecipe implements JsonSerializable
{
    /**
     * @param  array<string,mixed>  $provenance
     * @param  array<string,mixed>  $baseConstraints
     * @param  list<array<string,mixed>>  $requiredModifiers
     * @param  list<array<string,mixed>>  $optionalModifiers
     * @param  list<array<string,mixed>>  $excludedModifiers
     * @param  array<string,string>  $minimumValues
     * @param  array<string,int>  $weights
     * @param  list<array<string,mixed>>  $dependencies
     * @param  list<array<string,mixed>>  $unsupportedFilters
     */
    public function __construct(
        public GameEdition $gameEdition,
        public RulesetReference $ruleset,
        public string $slot,
        public ?string $itemClass,
        public array $baseConstraints,
        public ?string $rarity,
        public ?string $influenceOrEditionEquivalent,
        public ?string $corruptionConstraints,
        /** @var list<array<string,mixed>> */ public array $requiredModifiers,
        /** @var list<array<string,mixed>> */ public array $optionalModifiers,
        /** @var list<array<string,mixed>> */ public array $excludedModifiers,
        /** @var array<string,string> */ public array $minimumValues,
        /** @var array<string,int> */ public array $weights,
        /** @var list<array<string,mixed>> */ public array $dependencies,
        public string $broadRecipe,
        public string $strictRecipe,
        public string $explanation,
        public array $provenance,
        /** @var list<array<string,mixed>> */ public array $unsupportedFilters = [],
    ) {}

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'game_edition' => $this->gameEdition->value,
            'ruleset' => $this->ruleset,
            'slot' => $this->slot,
            'item_class' => $this->itemClass,
            'base_constraints' => $this->baseConstraints,
            'rarity' => $this->rarity,
            'influence_or_edition_equivalent' => $this->influenceOrEditionEquivalent,
            'corruption_constraints' => $this->corruptionConstraints,
            'required_modifiers' => $this->requiredModifiers,
            'optional_modifiers' => $this->optionalModifiers,
            'excluded_modifiers' => $this->excludedModifiers,
            'minimum_values' => $this->minimumValues,
            'weights' => $this->weights,
            'dependencies' => $this->dependencies,
            'broad_recipe' => $this->broadRecipe,
            'strict_recipe' => $this->strictRecipe,
            'explanation' => $this->explanation,
            'provenance' => $this->provenance,
            'unsupported_filters' => $this->unsupportedFilters,
        ];
    }
}
