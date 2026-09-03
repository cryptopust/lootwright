<?php

namespace Lootwright\Domain\Recommendations;

final readonly class ConstraintEvaluation
{
    public function __construct(public bool $allowed, public ?string $reason = null, public int $scoreAdjustment = 0) {}
}
