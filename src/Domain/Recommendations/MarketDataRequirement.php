<?php

namespace Lootwright\Domain\Recommendations;

enum MarketDataRequirement: string
{
    case NotRequired = 'not_required';
    case Required = 'required';
}
