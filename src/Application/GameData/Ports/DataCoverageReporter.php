<?php

namespace Lootwright\Application\GameData\Ports;

use Lootwright\Application\GameData\DTO\DataCoverageEntry;
use Lootwright\Domain\Shared\Game\GameEdition;

interface DataCoverageReporter
{
    /** @return list<DataCoverageEntry> */
    public function forEdition(GameEdition $edition): array;
}
