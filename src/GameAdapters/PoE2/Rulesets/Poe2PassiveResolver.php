<?php

namespace Lootwright\GameAdapters\PoE2\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\PassiveNode;

final readonly class Poe2PassiveResolver
{
    public function __construct(private Poe2Ruleset $ruleset) {}

    public function resolve(string $id): ?PassiveNode
    {
        $value = $this->ruleset->find(CanonicalEntityType::PassiveNode, $id);

        return $value instanceof PassiveNode ? $value : null;
    }
}
