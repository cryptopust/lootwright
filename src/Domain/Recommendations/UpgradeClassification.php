<?php

namespace Lootwright\Domain\Recommendations;

enum UpgradeClassification: string
{
    case Structural = 'structural';
    case Mandatory = 'mandatory';
    case HighImpact = 'high-impact';
    case Conditional = 'conditional';
    case Luxury = 'luxury';
    case RequiresMarketCheck = 'requires-market-check';
}
