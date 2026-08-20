<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class ItemBase extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::ItemBase;
    }
}
