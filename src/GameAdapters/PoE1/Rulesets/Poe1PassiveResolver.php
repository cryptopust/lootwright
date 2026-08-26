<?php

namespace Lootwright\GameAdapters\PoE1\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;

final readonly class Poe1PassiveResolver
{
    public function __construct(private Poe1CanonicalResolver $canonical) {}

    public function resolve(string $identifier): ?CanonicalGameEntity
    {
        return $this->canonical->resolve(CanonicalEntityType::PassiveNode, $identifier)
            ?? $this->canonical->resolve(CanonicalEntityType::Keystone, $identifier);
    }
}
