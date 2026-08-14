<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;

final readonly class SlotDependencyPlan
{
    public function __construct(
        public ItemSlotId $slot,
        public string $reason,
        public Finding $finding,
    ) {}
}
