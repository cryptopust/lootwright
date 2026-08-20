<?php

namespace Lootwright\Application\Rulesets\DTO;

final readonly class SourceSnapshotRecord
{
    public function __construct(
        public string $importRunId,
        public ?string $snapshotId,
        public string $status,
        public bool $replayed,
        public ?string $conflictId = null,
    ) {}
}
