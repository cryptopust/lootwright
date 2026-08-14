<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;

final readonly class PruneExpiredArtifacts
{
    public function __construct(
        private WorkflowRepository $repository,
        private ArtifactStorage $storage,
    ) {}

    public function handle(): int
    {
        $count = 0;

        foreach ($this->repository->expiredArtifacts() as $artifact) {
            $this->storage->delete($artifact->blobKey);
            $this->repository->expireArtifact($artifact->id);
            $count++;
        }

        return $count;
    }
}
