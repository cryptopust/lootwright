<?php

namespace Lootwright\Domain\Recommendations;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Budget;

final readonly class MarketPriceEvidence implements JsonSerializable
{
    public function __construct(
        public Budget $price,
        public string $sourceKey,
        public string $sourceVersion,
        public DateTimeImmutable $fetchedAt,
        public DateTimeImmutable $expiresAt,
        public string $checksumSha256,
        public MarketEvidenceFreshness $freshness,
        public bool $policyAllowed,
        public ?GameEdition $edition = null,
        public ?string $league = null,
        public int $sampleSize = 0,
        public int $listingCount = 0,
        public int $confidenceBasisPoints = 0,
        public int $liquidityBasisPoints = 0,
        public ?Budget $p25 = null,
        public ?Budget $p75 = null,
        public ?Budget $p90 = null,
        public int $outliersRejected = 0,
    ) {
        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceKey) !== 1
            || trim($sourceVersion) === ''
            || $expiresAt <= $fetchedAt
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || ! $policyAllowed
            || ($edition !== null && trim((string) $league) === '')
            || $sampleSize < 0 || $listingCount < 0
            || $confidenceBasisPoints < 0 || $confidenceBasisPoints > 10_000
            || $liquidityBasisPoints < 0 || $liquidityBasisPoints > 10_000
            || $outliersRejected < 0
        ) {
            throw new InvalidArgumentException('Market price evidence requires approved-style provenance, timestamps, and checksum.');
        }
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'price' => $this->price,
            'source_key' => $this->sourceKey,
            'source_version' => $this->sourceVersion,
            'fetched_at' => $this->fetchedAt->format(DATE_ATOM),
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
            'checksum_sha256' => $this->checksumSha256,
            'freshness' => $this->freshness->value,
            'policy_allowed' => true,
            'edition' => $this->edition?->value,
            'league' => $this->league,
            'sample_size' => $this->sampleSize,
            'listing_count' => $this->listingCount,
            'confidence_basis_points' => $this->confidenceBasisPoints,
            'liquidity_basis_points' => $this->liquidityBasisPoints,
            'p25' => $this->p25,
            'p75' => $this->p75,
            'p90' => $this->p90,
            'outliers_rejected' => $this->outliersRejected,
        ];
    }
}
