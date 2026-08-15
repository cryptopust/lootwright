<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class BuildDeletionResult
{
    public function __construct(
        public string $buildId,
        public int $analysesDeleted,
        public ?string $blobKey,
    ) {}
}
