<?php

namespace Lootwright\Domain\Market;

use DateTimeImmutable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use RuntimeException;

/** Deterministic statistics from approved listing observations; no floating point or external calls. */
final class MarketObservationBuilder
{
    /** @param list<string> $prices */
    public static function build(
        GameEdition $edition,
        string $source,
        string $sourceVersion,
        string $league,
        CurrencyCode $currency,
        array $prices,
        int $listingCount,
        DateTimeImmutable $observedAt,
        DateTimeImmutable $expiresAt,
    ): MarketObservation {
        $values = [];
        foreach ($prices as $price) {
            $budget = Budget::fromDecimal($currency, $price);
            if ($budget->isFailure()) {
                continue;
            }
            $values[] = $budget->value()->amount;
        }
        if ($values === []) {
            throw new RuntimeException('Market statistics require at least one valid price.');
        }
        usort($values, self::compareDecimal(...));
        /** @var list<string> $filtered */
        $filtered = self::rejectOutliers($values);
        $outliers = count($values) - count($filtered);
        $median = self::percentile($filtered, 50);
        $p25 = self::percentile($filtered, 25);
        $p75 = self::percentile($filtered, 75);
        $p90 = self::percentile($filtered, 90);
        $sample = count($filtered);
        $confidence = min(10_000, 2_000 + ($sample * 400));
        $liquidity = min(10_000, $listingCount * 100);
        $make = static fn (string $amount): Budget => Budget::fromDecimal($currency, $amount)->value();

        return new MarketObservation($edition, $source, $sourceVersion, $league, $observedAt, $expiresAt, $make($median), $make($p25), $make($p75), $make($p90), $listingCount, $sample, $outliers, $confidence, $liquidity);
    }

    /** @param list<string> $values
     * @return list<string>
     */
    private static function rejectOutliers(array $values): array
    {
        if (count($values) < 8) {
            return $values;
        }

        // A bounded, deterministic trim protects the estimate from a small
        // number of extreme listings without inventing an IQR calculation.
        return array_slice($values, 1, -1);
    }

    /** @param list<string> $values */
    private static function percentile(array $values, int $percentile): string
    {
        $index = (int) ceil((count($values) * $percentile) / 100) - 1;

        return $values[max(0, min(count($values) - 1, $index))];
    }

    private static function compareDecimal(string $left, string $right): int
    {
        [$leftWhole, $leftFraction] = array_pad(explode('.', $left, 2), 2, '');
        [$rightWhole, $rightFraction] = array_pad(explode('.', $right, 2), 2, '');
        $leftWhole = ltrim($leftWhole, '0') ?: '0';
        $rightWhole = ltrim($rightWhole, '0') ?: '0';
        if (strlen($leftWhole) !== strlen($rightWhole)) {
            return strlen($leftWhole) <=> strlen($rightWhole);
        }
        $whole = strcmp($leftWhole, $rightWhole);
        if ($whole !== 0) {
            return $whole <=> 0;
        }

        return strcmp(str_pad($leftFraction, 4, '0'), str_pad($rightFraction, 4, '0')) <=> 0;
    }
}
