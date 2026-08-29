<?php

namespace Lootwright\Domain\Market;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Budget;

/** A complete market observation. A price is never represented without context. */
final readonly class MarketObservation implements JsonSerializable
{
    public function __construct(
        public GameEdition $edition,
        public string $source,
        public string $sourceVersion,
        public string $league,
        public DateTimeImmutable $observedAt,
        public DateTimeImmutable $expiresAt,
        public Budget $median,
        public Budget $p25,
        public Budget $p75,
        public Budget $p90,
        public int $listingCount,
        public int $sampleSize,
        public int $outliersRejected,
        public int $confidenceBasisPoints,
        public int $liquidityBasisPoints,
        public int $ttlSeconds,
    ) {
        if (trim($source) === '' || trim($sourceVersion) === '' || trim($league) === ''
            || $expiresAt <= $observedAt || $listingCount < 0 || $sampleSize < 1
            || $outliersRejected < 0 || $outliersRejected > $sampleSize
            || $confidenceBasisPoints < 0 || $confidenceBasisPoints > 10_000
            || $liquidityBasisPoints < 0 || $liquidityBasisPoints > 10_000
            || $ttlSeconds < 1 || $ttlSeconds !== $expiresAt->getTimestamp() - $observedAt->getTimestamp()
            || ! $median->currency->equals($p25->currency)
            || ! $median->currency->equals($p75->currency)
            || ! $median->currency->equals($p90->currency)
        ) {
            throw new InvalidArgumentException('Market observations require complete, edition-scoped and bounded context.');
        }
    }

    public function isFresh(DateTimeImmutable $at): bool
    {
        return $at >= $this->observedAt && $at < $this->expiresAt;
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'source' => $this->source,
            'source_version' => $this->sourceVersion,
            'league' => $this->league,
            'observed_at' => $this->observedAt->format(DATE_ATOM),
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
            'median' => $this->median,
            'trimmed_median' => $this->median,
            'p25' => $this->p25,
            'p75' => $this->p75,
            'p90' => $this->p90,
            'listing_count' => $this->listingCount,
            'sample_size' => $this->sampleSize,
            'outliers_rejected' => $this->outliersRejected,
            'confidence_basis_points' => $this->confidenceBasisPoints,
            'liquidity_basis_points' => $this->liquidityBasisPoints,
            'ttl_seconds' => $this->ttlSeconds,
        ];
    }
}
