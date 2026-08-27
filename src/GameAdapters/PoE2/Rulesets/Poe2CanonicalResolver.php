<?php

namespace Lootwright\GameAdapters\PoE2\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\Ascendancy;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CharacterClass;

final readonly class Poe2CanonicalResolver
{
    public function __construct(private Poe2Ruleset $ruleset) {}

    public function characterClass(string $id): ?CharacterClass
    {
        $value = $this->ruleset->find(CanonicalEntityType::CharacterClass, $id);

        return $value instanceof CharacterClass ? $value : null;
    }

    public function ascendancy(string $id): ?Ascendancy
    {
        $value = $this->ruleset->find(CanonicalEntityType::Ascendancy, $id);

        return $value instanceof Ascendancy ? $value : null;
    }
}
