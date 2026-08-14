<?php

namespace Lootwright\Domain\Shared\Value;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class Locale implements JsonSerializable
{
    private function __construct(public string $value) {}

    public static function from(string $value): DomainResult
    {
        $value = trim($value);

        if (preg_match('/^[a-z]{2,3}(?:-[A-Z][a-z]{3})?(?:-(?:[A-Z]{2}|\d{3}))?$/D', $value) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidLocale,
                'The locale must be a canonical BCP 47 language tag.',
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
