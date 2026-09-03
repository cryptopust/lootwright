<?php

namespace Lootwright\Application\GameData\Ports;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Shared\Game\GameEdition;

interface SourceAuthorityRegistry
{
    public function tier(GameEdition $edition, CanonicalEntityType $category, string $sourceCode): ?string;
}
