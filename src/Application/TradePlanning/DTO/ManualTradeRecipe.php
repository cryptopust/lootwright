<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;

final readonly class ManualTradeRecipe implements JsonSerializable
{
    /**
     * @param  list<ResolvedItemConstraint>  $constraints
     * @param  list<RecipeDependency>  $dependencies
     * @param  list<UnresolvedRequirement>  $unresolvedRequirements
     * @param  array{currency: string, amount: string}|null  $budget
     * @param  array{id: string, version: string, checksum_sha256: string, patch: string, league: ?string, parser_version: string, source_id: string, source_version: string}  $ruleset
     */
    public function __construct(
        public string $gameEdition,
        public string $platformRealm,
        public ?string $league,
        public string $slot,
        public ?array $budget,
        public ?ResolvedItemTarget $itemTarget,
        public RecipeVariant $broadFallback,
        public RecipeVariant $strict,
        public array $constraints,
        public ?ResolvedItemConstraint $affixPreference,
        public array $dependencies,
        public array $unresolvedRequirements,
        public array $ruleset,
        public int $confidenceBasisPoints,
        public string $officialTradeHomepage,
        public string $homepageLinkLabel,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'game_edition' => $this->gameEdition,
            'platform_realm' => $this->platformRealm,
            'league' => $this->league,
            'slot' => $this->slot,
            'budget' => $this->budget,
            'item_target' => $this->itemTarget,
            'broad_fallback' => $this->broadFallback,
            'strict' => $this->strict,
            'constraints' => $this->constraints,
            'affix_preference' => $this->affixPreference,
            'dependencies' => $this->dependencies,
            'unresolved_requirements' => $this->unresolvedRequirements,
            'ruleset' => $this->ruleset,
            'confidence_basis_points' => $this->confidenceBasisPoints,
            'official_trade_homepage' => [
                'label' => $this->homepageLinkLabel,
                'url' => $this->officialTradeHomepage,
                'interaction' => 'single_manual_click',
            ],
        ];
    }
}
