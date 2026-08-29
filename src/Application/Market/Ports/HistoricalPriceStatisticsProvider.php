<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\TradeSearchRequest;

interface HistoricalPriceStatisticsProvider
{
    public function historicalStatistics(TradeSearchRequest $request): MarketEstimate;
}
