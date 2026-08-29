<?php

namespace Lootwright\GameAdapters\PoE1\Market;

use Lootwright\Application\Market\RepositoryMarketProvider;
use Lootwright\Application\Market\Ports\MarketObservationRepository;
use Lootwright\Domain\Shared\Game\GameEdition;

/** PoE1-only adapter for operator-approved, locally cached poe.ninja economy snapshots. */
final readonly class PoeNinjaTradeProvider extends RepositoryMarketProvider
{
    public function __construct(MarketObservationRepository $repository)
    {
        parent::__construct(GameEdition::Poe1, $repository);
    }
}
