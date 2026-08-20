<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class ModifierDefinition extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::ModifierDefinition;
    }
}
