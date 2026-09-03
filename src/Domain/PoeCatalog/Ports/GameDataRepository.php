<?php

namespace Lootwright\Domain\PoeCatalog\Ports;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;
use Lootwright\Domain\Shared\Game\GameEdition;

interface GameDataRepository
{
    public function find(
        GameEdition $edition,
        string $rulesetVersionId,
        CanonicalEntityType $type,
        string $externalId,
    ): ?CanonicalGameEntity;

    /** @return list<CanonicalGameEntity> */
    public function listForRuleset(
        GameEdition $edition,
        string $rulesetVersionId,
        ?CanonicalEntityType $type = null,
    ): array;
}
