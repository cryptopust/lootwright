<?php

namespace Lootwright\Domain\Recommendations;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class RecommendationImpact implements JsonSerializable
{
    /** @param  array<string, int>  $dimensions */
    private function __construct(public array $dimensions) {}

    /**
     * @param  array<array-key, mixed>  $dimensions
     */
    public static function create(array $dimensions): DomainResult
    {
        if ($dimensions === []) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EmptyCollection,
                'Recommendation impact requires at least one declared dimension.',
            ));
        }

        $validatedDimensions = [];

        foreach ($dimensions as $dimension => $basisPoints) {
            if (! is_string($dimension)
                || preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $dimension) !== 1
                || ! is_int($basisPoints)
                || $basisPoints < -10_000
                || $basisPoints > 10_000
            ) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Impact dimensions require canonical keys and signed basis-point values.',
                ));
            }

            $validatedDimensions[$dimension] = $basisPoints;
        }

        ksort($validatedDimensions, SORT_STRING);

        return DomainResult::success(new self($validatedDimensions));
    }

    /** @return array<string, int> */
    public function jsonSerialize(): array
    {
        return $this->dimensions;
    }
}
