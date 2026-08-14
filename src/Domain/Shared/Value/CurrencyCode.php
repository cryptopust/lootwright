<?php

namespace Lootwright\Domain\Shared\Value;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class CurrencyCode implements JsonSerializable
{
    private function __construct(public string $value) {}

    public static function from(string $value): DomainResult
    {
        $value = trim($value);

        if (preg_match('/^[A-Z][A-Z0-9_]{2,11}$/D', $value) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidIdentifier,
                'The currency code must be a canonical uppercase code.',
            ));
        }

        return DomainResult::success(new self($value));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
