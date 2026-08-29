<?php

namespace Lootwright\Domain\Recommendations;

use JsonSerializable;
use Lootwright\Domain\Shared\Value\Budget;

/** Market ROI context; it cannot alter deterministic findings or game scores. */
final readonly class UpgradeMarketValue implements JsonSerializable
{
    public function __construct(public int $estimatedBenefitBasisPoints, public ?Budget $estimatedMarketCost, public int $availabilityBasisPoints, public int $dependencyCostBasisPoints, public int $valueScoreBasisPoints, public int $priceConfidenceBasisPoints)
    {
        foreach ([$estimatedBenefitBasisPoints, $availabilityBasisPoints, $dependencyCostBasisPoints, $valueScoreBasisPoints, $priceConfidenceBasisPoints] as $value) {
            if ($value < 0 || $value > 10_000) throw new \InvalidArgumentException('Upgrade market values must be bounded basis points.');
        }
    }

    public function jsonSerialize(): array
    {
        return ['estimated_benefit_basis_points' => $this->estimatedBenefitBasisPoints, 'estimated_market_cost' => $this->estimatedMarketCost, 'availability_basis_points' => $this->availabilityBasisPoints, 'dependency_cost_basis_points' => $this->dependencyCostBasisPoints, 'value_score_basis_points' => $this->valueScoreBasisPoints, 'price_confidence_basis_points' => $this->priceConfidenceBasisPoints];
    }
}
