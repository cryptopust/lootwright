<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\TradeProviderCapabilities;
use Lootwright\Application\Market\TradeSearchRequest;
use Lootwright\Domain\Shared\Game\GameEdition;

interface TradeProvider
{
    public function edition(): GameEdition;

    public function capabilities(): TradeProviderCapabilities;

    public function supportsSearch(): bool;

    public function supportsListings(): bool;

    public function supportsPriceStats(): bool;

    public function supportsHistoricalStats(): bool;

    public function supportsEncodedSearch(): bool;

    public function supportsDeepLinks(): bool;

    /** A provider may return no estimate when its policy gate or source is unavailable. */
    public function marketEstimate(TradeSearchRequest $request): MarketEstimate;
}
