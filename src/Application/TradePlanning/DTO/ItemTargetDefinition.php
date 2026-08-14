<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Value\Confidence;

final readonly class ItemTargetDefinition
{
    public function __construct(
        public string $code,
        public string $exactCategoryLabel,
        public ?string $exactBaseFamilyLabel,
        public RuleReference $rule,
        public Confidence $confidence,
    ) {}
}
