<?php

namespace Lootwright\Application\Market\Ports;

use Lootwright\Domain\Shared\Game\GameEdition;

interface MarketObservationRepository
{
    /** @return list<array{price:string, currency:string, source:string, source_version:string, observed_at:\DateTimeImmutable, expires_at:\DateTimeImmutable, listing_count:int}> */
    /** @param array<string,mixed> $filters
     *  @return list<array{price:string,currency:string,source:string,source_version:string,observed_at:\DateTimeImmutable,expires_at:\DateTimeImmutable,listing_count:int}> */
    public function prices(GameEdition $edition, string $league, array $filters): array;
}
