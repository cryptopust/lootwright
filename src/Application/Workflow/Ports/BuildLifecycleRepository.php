<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Application\Workflow\DTO\BuildDeletionResult;

interface BuildLifecycleRepository
{
    public function blobKeyForOwner(string $buildId, string $ownerId): ?string;

    public function deleteBuildForOwner(string $buildId, string $ownerId): ?BuildDeletionResult;
}
