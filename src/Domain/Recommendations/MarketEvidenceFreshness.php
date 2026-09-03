<?php

namespace Lootwright\Domain\Recommendations;

enum MarketEvidenceFreshness: string
{
    case Fresh = 'fresh';
    case StaleUsable = 'stale_usable';
    case Expired = 'expired';
    case Unavailable = 'unavailable';
}
