<?php

namespace Lootwright\Application\Market;

use Lootwright\Application\Market\Ports\MarketEstimateCache;
use Lootwright\Application\Market\Ports\TradeProvider;

/** Uses approved local data first, then a non-expired cache, otherwise no-price. */
final readonly class CachedMarketProvider
{
    /** @param array<string,MarketEstimate>|MarketEstimateCache $cache */
    public function __construct(private TradeProvider $provider, private array|MarketEstimateCache $cache = new NullMarketEstimateCache) {}

    public function estimate(TradeSearchRequest $request, string $cacheKey, \DateTimeImmutable $now): MarketEstimate
    {
        $live = $this->provider->marketEstimate($request);
        if ($live->isCurrent()) {
            if ($this->cache instanceof MarketEstimateCache) {
                $this->cache->put($cacheKey, $live, $live->observation->expiresAt);
            }

            return $live;
        }
        $cached = $this->cache instanceof MarketEstimateCache ? $this->cache->get($cacheKey) : ($this->cache[$cacheKey] ?? null);
        if ($cached?->observation !== null && $cached->observation->isFresh($now)) {
            return new MarketEstimate(MarketEstimateStatus::Cached, $cached->observation, 'Live market source unavailable; using a fresh cached observation.');
        }

        return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'No live or fresh cached market observation is available.');
    }
}
