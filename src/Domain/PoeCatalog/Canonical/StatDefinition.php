<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class StatDefinition extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::StatDefinition;
    }
}
