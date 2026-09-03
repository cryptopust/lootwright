<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class PassiveNode extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::PassiveNode;
    }
}
