<?php

namespace Lootwright\GameAdapters\PoE1\Market;

use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\MarketEstimateStatus;
use Lootwright\Application\Market\Ports\TradeProvider;
use Lootwright\Application\Market\TradeProviderCapabilities;
use Lootwright\Application\Market\TradeSearchRequest;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Policy-safe default: capabilities are explicit, but no runtime network access is performed. */
final readonly class Poe1TradeProvider implements TradeProvider
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    public function capabilities(): TradeProviderCapabilities
    {
        return new TradeProviderCapabilities(priceStats: false);
    }

    public function supportsSearch(): bool
    {
        return $this->capabilities()->supportsSearch();
    }

    public function supportsListings(): bool
    {
        return $this->capabilities()->supportsListings();
    }

    public function supportsPriceStats(): bool
    {
        return $this->capabilities()->supportsPriceStats();
    }

    public function supportsHistoricalStats(): bool
    {
        return $this->capabilities()->supportsHistoricalStats();
    }

    public function supportsEncodedSearch(): bool
    {
        return $this->capabilities()->supportsEncodedSearch();
    }

    public function supportsDeepLinks(): bool
    {
        return $this->capabilities()->supportsDeepLinks();
    }

    public function marketEstimate(TradeSearchRequest $request): MarketEstimate
    {
        return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'No approved PoE1 market provider is enabled for this request.');
    }
}
