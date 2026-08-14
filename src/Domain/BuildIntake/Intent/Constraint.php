<?php

namespace Lootwright\Domain\BuildIntake\Intent;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class Constraint implements JsonSerializable
{
    private function __construct(
        public string $code,
        public string $value,
        public UpgradePriority $priority,
    ) {}

    public static function create(string $code, string $value, UpgradePriority $priority): DomainResult
    {
        $code = trim($code);
        $value = trim($value);

        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $code) !== 1
            || $value === ''
            || mb_strlen($value) > 256
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value) === 1
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A constraint requires a canonical code and a bounded plain-text value.',
            ));
        }

        return DomainResult::success(new self($code, $value, $priority));
    }

    /** @return array{code: string, value: string, priority: int} */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'value' => $this->value, 'priority' => $this->priority->value];
    }
}
