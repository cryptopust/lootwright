<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class ContentGoalDefinition extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::ContentGoalDefinition;
    }
}
