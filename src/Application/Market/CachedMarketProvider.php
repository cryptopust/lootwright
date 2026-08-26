<?php

namespace Lootwright\Application\Market;

use Lootwright\Application\Market\Ports\TradeProvider;

/** Uses a provider's approved live result first, then a non-expired cache, otherwise no-price. */
final readonly class CachedMarketProvider
{
    /** @param array<string,MarketEstimate> $cache */
    public function __construct(private TradeProvider $provider, private array $cache = []) {}

    public function estimate(TradeSearchRequest $request, string $cacheKey, \DateTimeImmutable $now): MarketEstimate
    {
        $live = $this->provider->marketEstimate($request);
        if ($live->isCurrent()) {
            return $live;
        }
        $cached = $this->cache[$cacheKey] ?? null;
        if ($cached?->observation !== null && $cached->observation->isFresh($now)) {
            return new MarketEstimate(MarketEstimateStatus::Cached, $cached->observation, 'Live market source unavailable; using a fresh cached observation.');
        }

        return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'No live or fresh cached market observation is available.');
    }
}
