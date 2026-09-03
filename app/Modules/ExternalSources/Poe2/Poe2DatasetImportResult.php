<?php

namespace App\Modules\ExternalSources\Poe2;

final readonly class Poe2DatasetImportResult
{
    public function __construct(
        public string $status,
        public string $sourceVersion,
        public string $sourceChecksumSha256,
        public string $normalizedChecksumSha256,
        public int $recordCount,
        public ?string $snapshotId = null,
        public ?string $rulesetId = null,
        public bool $replayed = false,
        public ?string $failureCode = null,
    ) {}
}
