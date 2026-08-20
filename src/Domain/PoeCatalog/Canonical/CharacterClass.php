<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class CharacterClass extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::CharacterClass;
    }
}
