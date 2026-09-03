<?php

namespace Lootwright\Domain\Recommendations;

final readonly class BudgetEvaluation
{
    public function __construct(
        public bool $allowed,
        public BudgetUncertainty $uncertainty,
        public ?string $reason = null,
    ) {}
}
