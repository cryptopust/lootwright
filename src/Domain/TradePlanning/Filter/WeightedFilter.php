<?php

namespace Lootwright\Domain\TradePlanning\Filter;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class WeightedFilter implements JsonSerializable
{
    private function __construct(
        public GameEdition $edition,
        public string $code,
        public string $description,
        public int $weight,
    ) {}

    public static function create(
        GameEdition $edition,
        string $code,
        string $description,
        int $weight,
    ): DomainResult {
        $validated = FilterText::create($code, $description);

        if ($validated->isFailure()) {
            return DomainResult::failure($validated->error());
        }

        if ($weight < 1 || $weight > 100) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A weighted manual filter requires a weight from one to one hundred.',
            ));
        }

        $value = $validated->value();

        if (! $value instanceof FilterText) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Filter validation returned an unexpected value.',
            ));
        }

        return DomainResult::success(new self(
            $edition,
            $value->code,
            $value->description,
            $weight,
        ));
    }

    /** @return array{edition: string, code: string, description: string, weight: int} */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'code' => $this->code,
            'description' => $this->description,
            'weight' => $this->weight,
        ];
    }
}
