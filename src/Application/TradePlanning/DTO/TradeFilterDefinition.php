<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\PoeCatalog\Identifier\ModifierId;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Value\Confidence;

final readonly class TradeFilterDefinition
{
    /** @param list<string> $conflictingModifierIds */
    public function __construct(
        public ModifierId $modifierId,
        public string $exactLabel,
        public RuleReference $rule,
        public Confidence $confidence,
        public array $conflictingModifierIds = [],
    ) {}
}
