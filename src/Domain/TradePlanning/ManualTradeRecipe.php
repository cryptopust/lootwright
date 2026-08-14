<?php

namespace Lootwright\Domain\TradePlanning;

use JsonSerializable;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\TradePlanning\Filter\ExcludedFilter;
use Lootwright\Domain\TradePlanning\Filter\RequiredFilter;
use Lootwright\Domain\TradePlanning\Filter\WeightedFilter;

final readonly class ManualTradeRecipe implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $required
     * @param  array<array-key, mixed>  $weighted
     * @param  array<array-key, mixed>  $excluded
     */
    private function __construct(
        public GameEdition $edition,
        public string $recommendationCode,
        public array $required,
        public array $weighted,
        public array $excluded,
        public ExplanationTrace $trace,
    ) {}

    /**
     * @param  array<array-key, mixed>  $required
     * @param  array<array-key, mixed>  $weighted
     * @param  array<array-key, mixed>  $excluded
     */
    public static function create(
        Recommendation $recommendation,
        array $required,
        array $weighted,
        array $excluded,
        ExplanationTrace $trace,
    ): DomainResult {
        $edition = $recommendation->edition;

        if ($trace->edition !== $edition || ($required === [] && $weighted === [])) {
            return DomainResult::failure(DomainError::because(
                $trace->edition !== $edition
                    ? DomainErrorCode::EditionMismatch
                    : DomainErrorCode::EmptyCollection,
                'A manual recipe requires an edition-matched trace and at least one positive filter.',
            ));
        }

        $keys = [];
        $validatedRequired = [];

        foreach ($required as $filter) {
            if (! $filter instanceof RequiredFilter || $filter->edition !== $edition) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every required filter must have the expected type and recipe edition.',
                ));
            }

            $key = RequiredFilter::class.':'.$filter->code;

            if (isset($keys[$key])) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::DuplicateValue,
                    'A manual recipe cannot contain duplicate filters.',
                ));
            }

            $keys[$key] = true;
            $validatedRequired[] = $filter;
        }

        $validatedWeighted = [];

        foreach ($weighted as $filter) {
            if (! $filter instanceof WeightedFilter || $filter->edition !== $edition) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every weighted filter must have the expected type and recipe edition.',
                ));
            }

            $key = WeightedFilter::class.':'.$filter->code;

            if (isset($keys[$key])) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::DuplicateValue,
                    'A manual recipe cannot contain duplicate filters.',
                ));
            }

            $keys[$key] = true;
            $validatedWeighted[] = $filter;
        }

        $validatedExcluded = [];

        foreach ($excluded as $filter) {
            if (! $filter instanceof ExcludedFilter || $filter->edition !== $edition) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every excluded filter must have the expected type and recipe edition.',
                ));
            }

            $key = ExcludedFilter::class.':'.$filter->code;

            if (isset($keys[$key])) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::DuplicateValue,
                    'A manual recipe cannot contain duplicate filters.',
                ));
            }

            $keys[$key] = true;
            $validatedExcluded[] = $filter;
        }

        return DomainResult::success(new self(
            $edition,
            $recommendation->code,
            $validatedRequired,
            $validatedWeighted,
            $validatedExcluded,
            $trace,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'recommendation_code' => $this->recommendationCode,
            'required' => $this->required,
            'weighted' => $this->weighted,
            'excluded' => $this->excluded,
            'trace' => $this->trace,
        ];
    }
}
