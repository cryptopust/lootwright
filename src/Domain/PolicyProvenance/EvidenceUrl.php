<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class EvidenceUrl implements JsonSerializable
{
    private function __construct(public string $value) {}

    public static function from(string $value): DomainResult
    {
        $value = trim($value);
        $parts = parse_url($value);

        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || ! is_string($parts['host'] ?? null)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
            || strlen($value) > 2048
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Evidence URLs must be bounded HTTPS URLs without credentials or fragments.',
            ));
        }

        return DomainResult::success(new self($value));
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
