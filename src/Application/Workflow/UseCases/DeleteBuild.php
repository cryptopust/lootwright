<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\BuildDeletionResult;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\BuildLifecycleRepository;
use Lootwright\Application\Workflow\Ports\TransactionManager;

final readonly class DeleteBuild
{
    public function __construct(
        private BuildLifecycleRepository $repository,
        private ArtifactStorage $storage,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $ownerId, string $buildId): BuildDeletionResult
    {
        $blobKey = $this->repository->blobKeyForOwner($buildId, $ownerId);

        if ($blobKey === null) {
            throw new WorkflowNotFound('The build was not found.');
        }

        $this->storage->delete($blobKey);
        $result = $this->transactions->run(fn (): ?BuildDeletionResult => $this->repository->deleteBuildForOwner($buildId, $ownerId));

        if (! $result instanceof BuildDeletionResult) {
            throw new WorkflowNotFound('The build was not found.');
        }

        return $result;
    }
}
