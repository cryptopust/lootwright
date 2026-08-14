<?php

namespace Lootwright\Domain\TradePlanning\Ports;

use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Shared\Error\DomainResult;

interface TradeRecipeCompiler
{
    public function compile(Recommendation $recommendation): DomainResult;
}
