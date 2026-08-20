<?php

namespace App\Modules\Rulesets\PassiveTree;

final readonly class GggPassiveTreeImportResult
{
    public function __construct(
        public string $status,
        public string $revision,
        public string $sourceChecksumSha256,
        public ?string $snapshotChecksumSha256,
        public ?string $snapshotId,
        public ?string $rulesetVersionId,
        public bool $replayed,
        public int $classCount,
        public int $nodeCount,
        public ?string $failureCode = null,
    ) {}
}
