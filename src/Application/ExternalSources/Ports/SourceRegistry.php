<?php

namespace Lootwright\Application\ExternalSources\Ports;

use Lootwright\Application\ExternalSources\DTO\SourceRegistryRecord;

interface SourceRegistry
{
    /** @return list<SourceRegistryRecord> */
    public function all(): array;

    public function find(string $sourceCode): ?SourceRegistryRecord;
}
