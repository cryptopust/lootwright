<?php

namespace Lootwright\Application\ExternalSources\Ports;

interface OfficialTradeSearchProvider
{
    public function unavailableReason(): string;
}
