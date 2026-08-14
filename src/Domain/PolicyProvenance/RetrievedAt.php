<?php

namespace Lootwright\Domain\PolicyProvenance;

use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Throwable;

final readonly class RetrievedAt implements JsonSerializable
{
    private function __construct(public string $value) {}

    public static function from(string $value): DomainResult
    {
        try {
            $date = new DateTimeImmutable($value);
        } catch (Throwable) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A retrieval timestamp must be a valid ISO-8601 timestamp.',
            ));
        }

        if ($date->format('P') !== '+00:00') {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A retrieval timestamp must use the UTC offset.',
            ));
        }

        return DomainResult::success(new self(
            $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        ));
    }

    public function dateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
