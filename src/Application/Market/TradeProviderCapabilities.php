<?php

namespace Lootwright\Application\Market;

/** Capability flags are intentionally independent: one source may read data without being allowed to search or link it. */
final readonly class TradeProviderCapabilities
{
    public function __construct(
        private bool $search = false,
        private bool $listings = false,
        private bool $priceStats = false,
        private bool $historicalStats = false,
        private bool $encodedSearch = false,
        private bool $deepLinks = false,
    ) {}

    public function supportsSearch(): bool
    {
        return $this->search;
    }

    public function supportsListings(): bool
    {
        return $this->listings;
    }

    public function supportsPriceStats(): bool
    {
        return $this->priceStats;
    }

    public function supportsHistoricalStats(): bool
    {
        return $this->historicalStats;
    }

    public function supportsEncodedSearch(): bool
    {
        return $this->encodedSearch;
    }

    public function supportsDeepLinks(): bool
    {
        return $this->deepLinks;
    }

    /** @return array<string,bool> */
    public function jsonSerialize(): array
    {
        return [
            'search' => $this->search,
            'listings' => $this->listings,
            'price_stats' => $this->priceStats,
            'historical_stats' => $this->historicalStats,
            'encoded_search' => $this->encodedSearch,
            'deep_links' => $this->deepLinks,
        ];
    }
}
