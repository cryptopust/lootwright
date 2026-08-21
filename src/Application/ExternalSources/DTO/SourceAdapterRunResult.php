<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class SourceAdapterRunResult
{
    public function __construct(
        public bool $success,
        public int $recordsImported,
        public ?string $failureCode = null,
    ) {}
}
