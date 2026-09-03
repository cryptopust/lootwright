<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

final readonly class SkillGem extends CanonicalGameEntity
{
    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::SkillGem;
    }
}
