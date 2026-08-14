<?php

namespace Lootwright\Application\TradePlanning\DTO;

use InvalidArgumentException;
use JsonSerializable;

final readonly class NumericRange implements JsonSerializable
{
    private function __construct(
        public ?string $minimum,
        public ?string $maximum,
    ) {}

    public static function create(?string $minimum, ?string $maximum): self
    {
        $minimum = self::normalize($minimum);
        $maximum = self::normalize($maximum);

        if ($minimum === null && $maximum === null) {
            throw new InvalidArgumentException('A numeric filter range requires a minimum or maximum.');
        }

        if ($minimum !== null && $maximum !== null && self::compare($minimum, $maximum) > 0) {
            throw new InvalidArgumentException('A numeric filter minimum cannot exceed its maximum.');
        }

        return new self($minimum, $maximum);
    }

    public function contains(self $other): bool
    {
        return ($this->minimum === null
                || ($other->minimum !== null && self::compare($this->minimum, $other->minimum) <= 0))
            && ($this->maximum === null
                || ($other->maximum !== null && self::compare($this->maximum, $other->maximum) >= 0));
    }

    /** @return array{minimum: ?string, maximum: ?string} */
    public function jsonSerialize(): array
    {
        return ['minimum' => $this->minimum, 'maximum' => $this->maximum];
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^-?(?:0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D', $value) !== 1) {
            throw new InvalidArgumentException('Filter values must be bounded canonical decimals.');
        }

        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return in_array($value, ['-0', ''], true) ? '0' : $value;
    }

    private static function compare(string $left, string $right): int
    {
        [$leftNegative, $leftMagnitude] = self::scaledMagnitude($left);
        [$rightNegative, $rightMagnitude] = self::scaledMagnitude($right);

        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        $comparison = strcmp($leftMagnitude, $rightMagnitude) <=> 0;

        return $leftNegative ? -$comparison : $comparison;
    }

    /** @return array{bool, string} */
    private static function scaledMagnitude(string $value): array
    {
        $negative = str_starts_with($value, '-');
        $unsigned = $negative ? substr($value, 1) : $value;
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        return [$negative, str_pad($whole, 15, '0', STR_PAD_LEFT).str_pad($fraction, 4, '0')];
    }
}
