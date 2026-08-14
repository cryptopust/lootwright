<?php

namespace Lootwright\Domain\Shared\Value;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class Confidence implements JsonSerializable
{
    private function __construct(public int $basisPoints) {}

    public static function fromBasisPoints(int $basisPoints): DomainResult
    {
        if ($basisPoints < 0 || $basisPoints > 10_000) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidConfidence,
                'Confidence must be between zero and ten thousand basis points.',
            ));
        }

        return DomainResult::success(new self($basisPoints));
    }

    public function isBelow(int $basisPoints): bool
    {
        return $this->basisPoints < $basisPoints;
    }

    public function jsonSerialize(): int
    {
        return $this->basisPoints;
    }
}
