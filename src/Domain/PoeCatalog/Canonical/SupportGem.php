<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class SupportGem extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::SupportGem;
    }
}
