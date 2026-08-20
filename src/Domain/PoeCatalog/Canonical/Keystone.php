<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class Keystone extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::Keystone;
    }
}
