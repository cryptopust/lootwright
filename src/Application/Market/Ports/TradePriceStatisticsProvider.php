<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\TradeSearchRequest;

interface TradePriceStatisticsProvider
{
    public function priceStatistics(TradeSearchRequest $request): MarketEstimate;
}
