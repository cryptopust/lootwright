<?php

namespace Lootwright\Application\ExternalSources\Ports;

interface ExternalSourceAdapterCatalog
{
    /** @return list<ExternalSourceAdapter> */
    public function all(): array;

    public function find(string $sourceCode): ?ExternalSourceAdapter;
}
