<?php

namespace Lootwright\GameAdapters\PoE2\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\ModifierDefinition;

final readonly class Poe2ModifierResolver
{
    public function __construct(private Poe2Ruleset $ruleset) {}

    public function resolve(string $id): ?ModifierDefinition
    {
        $value = $this->ruleset->find(CanonicalEntityType::ModifierDefinition, $id);

        return $value instanceof ModifierDefinition ? $value : null;
    }
}
