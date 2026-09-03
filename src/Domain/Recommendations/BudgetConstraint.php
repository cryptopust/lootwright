<?php

namespace Lootwright\Domain\Recommendations;

use JsonSerializable;
use Lootwright\Domain\Shared\Value\Budget;

final readonly class BudgetConstraint implements JsonSerializable
{
    private function __construct(public ?Budget $budget) {}

    public static function unknown(): self
    {
        return new self(null);
    }

    public static function limitedTo(Budget $budget): self
    {
        return new self($budget);
    }

    public function isKnown(): bool
    {
        return $this->budget !== null;
    }

    /** @return array{currency:string,amount:string}|null */
    public function jsonSerialize(): ?array
    {
        return $this->budget?->jsonSerialize();
    }
}
