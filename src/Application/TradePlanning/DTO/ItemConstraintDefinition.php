<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Value\Confidence;

final readonly class ItemConstraintDefinition
{
    public function __construct(
        public string $code,
        public string $exactLabel,
        public RuleReference $rule,
        public Confidence $confidence,
    ) {}
}
