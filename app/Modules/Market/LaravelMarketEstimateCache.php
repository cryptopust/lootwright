<?php

namespace App\Modules\Market;

use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository;
use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\Ports\MarketEstimateCache;

/** Uses Laravel's configured cache store (Valkey on Laravel Cloud when selected). */
final readonly class LaravelMarketEstimateCache implements MarketEstimateCache
{
    public function __construct(private Repository $cache) {}

    public function get(string $key): ?MarketEstimate
    {
        $value = $this->cache->get($this->key($key));

        return $value instanceof MarketEstimate ? $value : null;
    }

    public function put(string $key, MarketEstimate $estimate, DateTimeImmutable $expiresAt): void
    {
        $this->cache->put($this->key($key), $estimate, $expiresAt);
    }

    private function key(string $key): string
    {
        return 'market-estimate:v1:'.hash('sha256', $key);
    }
}
