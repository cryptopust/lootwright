<?php

namespace Lootwright\Application\TradePlanning\DTO;

use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Shared\Value\Confidence;

final readonly class SlotUpgradePlan
{
    /**
     * @param  list<SlotFilterIntent>  $filters
     * @param  list<string>  $constraintCodes
     * @param  list<SlotDependencyPlan>  $dependencies
     */
    public function __construct(
        public Recommendation $recommendation,
        public ItemSlotId $slot,
        public ?string $itemTargetCode,
        public array $filters,
        public array $constraintCodes,
        public ?string $affixPreferenceCode,
        public array $dependencies,
        public Confidence $confidence,
    ) {}
}
