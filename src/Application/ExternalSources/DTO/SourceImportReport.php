<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class SourceImportReport
{
    /** @param list<StagedSourceRecord> $records */
    public function __construct(
        public string $id,
        public string $importRunId,
        public string $status,
        public bool $replayed,
        public array $records,
        public ?string $sourceSnapshotId = null,
    ) {}
}
