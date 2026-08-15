<?php

namespace Lootwright\Application\Funding\Ports;

use Lootwright\Application\Funding\DTO\FundingStatus;

interface FundingStatusProvider
{
    public function current(): FundingStatus;
}
