<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Application\Market\MarketListing;
use Lootwright\Application\Market\TradeSearchRequest;

interface TradeListingProvider
{
    /** @return list<MarketListing> */
    public function listings(TradeSearchRequest $request): array;
}
