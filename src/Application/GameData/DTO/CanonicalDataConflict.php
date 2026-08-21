<?php

namespace Lootwright\Application\GameData\DTO;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class CanonicalDataConflict
{
    public function __construct(
        public GameEdition $edition,
        public CanonicalEntityType $category,
        public string $externalId,
        public SourceAuthorityCandidate $left,
        public SourceAuthorityCandidate $right,
        public string $reasonCode,
    ) {}
}
