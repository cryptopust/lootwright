<?php

namespace Lootwright\GameAdapters\PoE1\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;

final readonly class Poe1SkillResolver
{
    public function __construct(private Poe1CanonicalResolver $canonical) {}

    public function resolve(string $identifier, bool $support): ?CanonicalGameEntity
    {
        return $this->canonical->resolve($support ? CanonicalEntityType::SupportGem : CanonicalEntityType::SkillGem, $identifier);
    }
}
