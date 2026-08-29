<?php

namespace Lootwright\Application\Market;

enum TradeSearchMode: string
{
    case Broad = 'broad';
    case Strict = 'strict';
    case Budget = 'budget';
    case Alternative = 'alternative';
}
