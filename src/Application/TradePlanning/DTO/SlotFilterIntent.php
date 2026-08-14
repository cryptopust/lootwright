<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\PoeCatalog\Identifier\ModifierId;

final readonly class SlotFilterIntent
{
    public function __construct(
        public ModifierId $modifierId,
        public RecipeFilterMode $strictMode,
        public ?NumericRange $strictRange,
        public ?int $strictWeight,
        public RecipeFilterMode $broadMode,
        public ?NumericRange $broadRange,
        public ?int $broadWeight,
        public string $reason,
        public Finding $finding,
    ) {}
}
