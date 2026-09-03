<?php

namespace Lootwright\Domain\Analysis;

use JsonSerializable;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;

/** A deterministic, non-authoritative candidate for the recommendation layer. */
final readonly class RecommendationCandidate implements JsonSerializable
{
    /** @param list<string> $evidence */
    public function __construct(
        public string $id,
        public GameEdition $gameEdition,
        public RulesetIdentity $ruleset,
        public string $category,
        public string $action,
        public array $evidence,
        public int $confidenceBasisPoints = 10_000,
    ) {}

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'game_edition' => $this->gameEdition->value,
            'ruleset' => $this->ruleset,
            'category' => $this->category,
            'action' => $this->action,
            'evidence' => $this->evidence,
            'confidence_basis_points' => $this->confidenceBasisPoints,
        ];
    }
}
