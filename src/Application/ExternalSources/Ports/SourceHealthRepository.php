<?php

namespace Lootwright\Application\ExternalSources\Ports;

use Lootwright\Application\ExternalSources\DTO\SourceHealth;

interface SourceHealthRepository
{
    /** @return list<SourceHealth> */
    public function all(): array;
}
