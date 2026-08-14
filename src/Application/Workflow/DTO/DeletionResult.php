<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class DeletionResult
{
    /** @param list<string> $blobKeys */
    public function __construct(
        public int $artifactsDeleted,
        public int $analysesDeleted,
        public array $blobKeys,
    ) {}
}
