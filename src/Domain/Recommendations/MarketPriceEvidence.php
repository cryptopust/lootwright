<?php

namespace Lootwright\Domain\Recommendations;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
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
    ) {
        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceKey) !== 1
            || trim($sourceVersion) === ''
            || $expiresAt <= $fetchedAt
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || ! $policyAllowed
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
        ];
    }
}
