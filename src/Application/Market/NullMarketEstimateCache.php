<?php

namespace Lootwright\Application\Market;

use Lootwright\Application\Market\Ports\MarketEstimateCache;

final readonly class NullMarketEstimateCache implements MarketEstimateCache
{
    public function get(string $key): ?MarketEstimate { return null; }

    public function put(string $key, MarketEstimate $estimate, \DateTimeImmutable $expiresAt): void {}
}
