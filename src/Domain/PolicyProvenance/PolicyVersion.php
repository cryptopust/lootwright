<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class PolicyVersion implements JsonSerializable
{
    private function __construct(public string $value) {}

    public static function from(string $value): DomainResult
    {
        $value = trim($value);

        if (preg_match('/^[1-9][0-9]*\.[0-9]+\.[0-9]+$/D', $value) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidVersion,
                'A policy version must be a canonical semantic version.',
            ));
        }

        return DomainResult::success(new self($value));
    }

    public static function baseline(): self
    {
        return new self('1.0.0');
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
