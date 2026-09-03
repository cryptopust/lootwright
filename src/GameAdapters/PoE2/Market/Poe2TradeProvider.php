<?php

namespace Lootwright\GameAdapters\PoE2\Market;

use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\MarketEstimateStatus;
use Lootwright\Application\Market\Ports\TradeProvider;
use Lootwright\Application\Market\TradeProviderCapabilities;
use Lootwright\Application\Market\TradeSearchRequest;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class Poe2TradeProvider implements TradeProvider
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    public function capabilities(): TradeProviderCapabilities
    {
        return new TradeProviderCapabilities;
    }

    public function supportsSearch(): bool
    {
        return false;
    }

    public function supportsListings(): bool
    {
        return false;
    }

    public function supportsPriceStats(): bool
    {
        return false;
    }

    public function supportsHistoricalStats(): bool
    {
        return false;
    }

    public function supportsEncodedSearch(): bool
    {
        return false;
    }

    public function supportsDeepLinks(): bool
    {
        return false;
    }

    public function marketEstimate(TradeSearchRequest $request): MarketEstimate
    {
        return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'PoE2 market intelligence is unavailable until an approved edition-specific source exists.');
    }
}
