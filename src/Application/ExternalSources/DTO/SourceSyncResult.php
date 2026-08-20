<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class SourceSyncResult
{
    public function __construct(public bool $success, public int $quoteCount, public ?string $failureCode = null) {}
}
