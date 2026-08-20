<?php

namespace Lootwright\Application\ExternalSources\DTO;

use Lootwright\Domain\Shared\Game\GameEdition;

/** Immutable, normalized market context. It is evidence, never an AI-supplied fact. */
final readonly class PriceEvidence
{
    public function __construct(public string $sourceKey, public EconomySourceVersion $sourceVersion, public GameEdition $gameEdition, public string $league, public EconomyCategory $category, public string $externalId, public string $normalizedName, public string $normalizedValue, public string $primaryCurrency, public ?string $secondaryCurrency, public \DateTimeImmutable $fetchedAt, public \DateTimeImmutable $expiresAt, public SourceFreshness $freshness, public string $checksum) {}
}
