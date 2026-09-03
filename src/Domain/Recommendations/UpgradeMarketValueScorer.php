<?php

namespace Lootwright\Domain\Recommendations;

use Lootwright\Domain\Shared\Value\Budget;

final class UpgradeMarketValueScorer
{
    public function score(int $deterministicBenefitBasisPoints, ?Budget $cost, int $availabilityBasisPoints, int $dependencyCostBasisPoints, int $priceConfidenceBasisPoints): UpgradeMarketValue
    {
        $benefit = max(0, min(10_000, $deterministicBenefitBasisPoints));
        $availability = max(0, min(10_000, $availabilityBasisPoints));
        $dependency = max(0, min(10_000, $dependencyCostBasisPoints));
        $confidence = max(0, min(10_000, $priceConfidenceBasisPoints));
        $costUnits = $cost === null ? 10_000 : max(1, self::minorUnits($cost->amount));
        $value = $cost === null ? 0 : min(10_000, intdiv($benefit * $availability * 100, $costUnits));
        $value = intdiv($value * max(0, 10_000 - $dependency), 10_000);

        return new UpgradeMarketValue($benefit, $cost, $availability, $dependency, $value, $confidence);
    }

    private static function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return min(1_000_000_000, ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0'));
    }
}
