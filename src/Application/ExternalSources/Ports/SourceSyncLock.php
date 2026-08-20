<?php

namespace Lootwright\Application\ExternalSources\Ports;

interface SourceSyncLock
{
    public function acquire(string $sourceKey, int $seconds): bool;

    public function release(): void;
}
