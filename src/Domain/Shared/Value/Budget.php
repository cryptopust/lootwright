<?php

namespace Lootwright\Domain\Shared\Value;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class Budget implements JsonSerializable
{
    private function __construct(
        public CurrencyCode $currency,
        public string $amount,
    ) {}

    public static function fromDecimal(CurrencyCode $currency, string $amount): DomainResult
    {
        $amount = trim($amount);

        if (strlen($amount) > 20
            || preg_match('/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D', $amount) !== 1
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidAmount,
                'A budget must be a non-negative decimal with at most four fractional digits.',
            ));
        }

        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
        }

        return DomainResult::success(new self($currency, $amount));
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency) && $this->amount === $other->amount;
    }

    /** @return array{currency: string, amount: string} */
    public function jsonSerialize(): array
    {
        return ['currency' => $this->currency->value, 'amount' => $this->amount];
    }
}
