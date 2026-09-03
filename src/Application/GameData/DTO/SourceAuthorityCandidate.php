<?php

namespace Lootwright\Application\GameData\DTO;

use InvalidArgumentException;

final readonly class SourceAuthorityCandidate
{
    public function __construct(
        public NormalizedGameDataRecord $record,
        public string $authorityTier,
    ) {
        if (! in_array($authorityTier, ['official_structured', 'approved_upstream', 'trusted_community', 'derived', 'heuristic'], true)) {
            throw new InvalidArgumentException('Source authority tier is invalid.');
        }
    }
}
