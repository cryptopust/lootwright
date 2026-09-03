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
     * @param  list<array<string,mixed>>  $tradeRequirements
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
        public array $tradeRequirements = [],
        public ?string $targetSlot = null,
        public ?UpgradeMarketValue $marketValue = null,
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
        foreach ($tradeRequirements as $requirement) {
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', (string) ($requirement['modifier_id'] ?? '')) !== 1
                || ! in_array($requirement['mode'] ?? null, ['required', 'optional', 'excluded'], true)
                || (isset($requirement['minimum']) && preg_match('/^-?(?:0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D', (string) $requirement['minimum']) !== 1)
                || (isset($requirement['weight']) && (! is_int($requirement['weight']) || $requirement['weight'] < 1 || $requirement['weight'] > 100))
            ) {
                throw new InvalidArgumentException('Trade requirements must use bounded canonical modifier constraints.');
            }
        }
        if ($targetSlot !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $targetSlot) !== 1) {
            throw new InvalidArgumentException('A Trade target slot must be a canonical slot identifier.');
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
            $this->tradeRequirements,
            $this->targetSlot,
            $this->marketValue,
        );
    }

    public function withPriceEvidence(?MarketPriceEvidence $evidence, UpgradeMarketValue|int|null $marketValue = null): self
    {
        $score = is_int($marketValue) ? $marketValue : $this->score;
        $value = $marketValue instanceof UpgradeMarketValue ? $marketValue : $this->marketValue;

        return new self(
            $this->id, $this->gameEdition, $this->ruleset, $this->classification,
            $this->title, $this->prerequisites, $this->conflicts, $this->dependentSlots,
            $this->affectedFindings, $this->expectedEffects, $this->budgetUncertainty,
            $this->marketDataRequirement, $score, $this->impossible,
            $this->impossibleReason, $evidence, $this->tradeRequirements, $this->targetSlot, $value,
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
            'trade_requirements' => $this->tradeRequirements,
            'target_slot' => $this->targetSlot,
            'market_value' => $this->marketValue,
        ];
    }
}
