<?php

namespace Lootwright\Application\Market;

use JsonSerializable;
use Lootwright\Domain\Shared\Value\Budget;

/** Safe listing summary. It intentionally contains no seller identity or interaction data. */
final readonly class MarketListing implements JsonSerializable
{
    public function __construct(public Budget $price, public string $source, public \DateTimeImmutable $observedAt) {}

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return ['price' => $this->price, 'source' => $this->source, 'observed_at' => $this->observedAt->format(DATE_ATOM)];
    }
}
