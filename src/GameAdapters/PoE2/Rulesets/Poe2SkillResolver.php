<?php

namespace Lootwright\GameAdapters\PoE2\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\SkillGem;
use Lootwright\Domain\PoeCatalog\Canonical\SupportGem;

final readonly class Poe2SkillResolver
{
    public function __construct(private Poe2Ruleset $ruleset) {}

    public function skill(string $id): ?SkillGem
    {
        $value = $this->ruleset->find(CanonicalEntityType::SkillGem, $id);

        return $value instanceof SkillGem ? $value : null;
    }

    public function support(string $id): ?SupportGem
    {
        $value = $this->ruleset->find(CanonicalEntityType::SupportGem, $id);

        return $value instanceof SupportGem ? $value : null;
    }
}
