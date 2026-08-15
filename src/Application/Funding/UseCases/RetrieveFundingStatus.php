<?php

namespace Lootwright\Application\Funding\UseCases;

use Lootwright\Application\Funding\DTO\FundingStatus;
use Lootwright\Application\Funding\Ports\FundingStatusProvider;

final readonly class RetrieveFundingStatus
{
    public function __construct(private FundingStatusProvider $provider) {}

    public function handle(): FundingStatus
    {
        return $this->provider->current();
    }
}
