<?php

namespace Lootwright\Domain\TradePlanning\Filter;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class ExcludedFilter implements JsonSerializable
{
    private function __construct(
        public GameEdition $edition,
        public string $code,
        public string $description,
    ) {}

    public static function create(GameEdition $edition, string $code, string $description): DomainResult
    {
        $validated = FilterText::create($code, $description);

        if ($validated->isFailure()) {
            return DomainResult::failure($validated->error());
        }

        $value = $validated->value();

        if (! $value instanceof FilterText) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Filter validation returned an unexpected value.',
            ));
        }

        return DomainResult::success(new self($edition, $value->code, $value->description));
    }

    /** @return array{edition: string, code: string, description: string} */
    public function jsonSerialize(): array
    {
        return ['edition' => $this->edition->value, 'code' => $this->code, 'description' => $this->description];
    }
}
