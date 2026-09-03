<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Application\Market\MarketEstimate;

interface MarketEstimateCache
{
    public function get(string $key): ?MarketEstimate;

    public function put(string $key, MarketEstimate $estimate, \DateTimeImmutable $expiresAt): void;
}
