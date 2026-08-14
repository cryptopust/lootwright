<?php

namespace Lootwright\Domain\TradePlanning\Filter;

use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class FilterText
{
    private function __construct(
        public string $code,
        public string $description,
    ) {}

    public static function create(string $code, string $description): DomainResult
    {
        $code = trim($code);
        $description = trim($description);

        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $code) !== 1
            || $description === ''
            || mb_strlen($description) > 240
            || preg_match('#(?:https?://|/api/|[{}])#i', $description) === 1
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Manual filter text must be bounded, descriptive, and contain no URL or API payload.',
            ));
        }

        return DomainResult::success(new self($code, $description));
    }
}
