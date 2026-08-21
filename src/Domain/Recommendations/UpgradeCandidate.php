<?php

namespace Lootwright\Domain\Recommendations;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class UpgradeCandidate implements JsonSerializable
{
    /** @param list<string> $prerequisites
     * @param  list<string>  $conflicts
     * @param  list<string>  $dependentSlots
     * @param  list<string>  $affectedFindings
     * @param  list<string>  $expectedEffects
     */
    public function __construct(
        public string $id,
        public GameEdition $gameEdition,
        public RulesetReference $ruleset,
        public UpgradeClassification $classification,
        public string $title,
        public array $prerequisites,
        public array $conflicts,
        public array $dependentSlots,
        public array $affectedFindings,
        public array $expectedEffects,
        public BudgetUncertainty $budgetUncertainty,
        public MarketDataRequirement $marketDataRequirement,
        public int $score,
        public bool $impossible = false,
        public ?string $impossibleReason = null,
        public ?MarketPriceEvidence $priceEvidence = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9._:-]{1,127}$/D', $id) !== 1 || trim($title) === '') {
            throw new InvalidArgumentException('An upgrade candidate requires a canonical ID and title.');
        }
        if ($gameEdition !== $ruleset->edition || $score < 0 || $score > 100_000) {
            throw new InvalidArgumentException('An upgrade candidate must match its ruleset edition and score bounds.');
        }
        if ($priceEvidence !== null && $marketDataRequirement !== MarketDataRequirement::Required) {
            throw new InvalidArgumentException('Price evidence is only valid for a market-dependent candidate.');
        }
    }

    public function evaluated(int $score, BudgetUncertainty $uncertainty, bool $impossible = false, ?string $reason = null): self
    {
        return new self(
            $this->id,
            $this->gameEdition,
            $this->ruleset,
            $this->classification,
            $this->title,
            $this->prerequisites,
            $this->conflicts,
            $this->dependentSlots,
            $this->affectedFindings,
            $this->expectedEffects,
            $uncertainty,
            $this->marketDataRequirement,
            $score,
            $impossible,
            $reason,
            $this->priceEvidence,
        );
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'game_edition' => $this->gameEdition->value,
            'ruleset_version' => $this->ruleset->version,
            'classification' => $this->classification->value,
            'title' => $this->title,
            'prerequisites' => $this->prerequisites,
            'conflicts' => $this->conflicts,
            'dependent_slots' => $this->dependentSlots,
            'affected_findings' => $this->affectedFindings,
            'expected_effects' => $this->expectedEffects,
            'budget_uncertainty' => $this->budgetUncertainty->value,
            'market_data_requirement' => $this->marketDataRequirement->value,
            'score' => $this->score,
            'impossible' => $this->impossible,
            'impossible_reason' => $this->impossibleReason,
            'price_evidence' => $this->priceEvidence,
        ];
    }
}
