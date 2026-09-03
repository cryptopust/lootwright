<?php

namespace Lootwright\GameAdapters\PoE1\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\ModifierDefinition;

final readonly class Poe1ModifierResolver
{
    public function __construct(private Poe1CanonicalResolver $canonical) {}

    public function resolve(string $identifier): ?ModifierDefinition
    {
        $entity = $this->canonical->resolve(CanonicalEntityType::ModifierDefinition, $identifier);

        return $entity instanceof ModifierDefinition ? $entity : null;
    }
}
