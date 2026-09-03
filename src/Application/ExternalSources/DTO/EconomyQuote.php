<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class EconomyQuote
{
    /** @param array<string, scalar|null> $confidenceMetadata */
    public function __construct(public PriceEvidence $evidence, public array $confidenceMetadata = []) {}
}
