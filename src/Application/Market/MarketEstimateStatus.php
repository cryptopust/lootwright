<?php

namespace Lootwright\Application\Market;

enum MarketEstimateStatus: string
{
    case Live = 'live';
    case Cached = 'cached';
    case NoPrice = 'no_price';
}
