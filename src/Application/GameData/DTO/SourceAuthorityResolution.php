<?php

namespace Lootwright\Application\GameData\DTO;

final readonly class SourceAuthorityResolution
{
    /** @param list<SourceAuthorityCandidate> $orderedCandidates */
    public function __construct(
        public ?NormalizedGameDataRecord $selected,
        public array $orderedCandidates,
        public bool $conflict,
        public string $reason,
    ) {}
}
