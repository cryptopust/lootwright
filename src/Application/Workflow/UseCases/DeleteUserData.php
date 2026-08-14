<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\DeletionResult;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\SupplementalUserDataEraser;
use Lootwright\Application\Workflow\Ports\TransactionManager;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;

final readonly class DeleteUserData
{
    public function __construct(
        private WorkflowRepository $repository,
        private ArtifactStorage $storage,
        private TransactionManager $transactions,
        private SupplementalUserDataEraser $supplementalEraser,
    ) {}

    public function handle(string $ownerId): DeletionResult
    {
        foreach ($this->repository->ownerArtifactBlobKeys($ownerId) as $blobKey) {
            $this->storage->delete($blobKey);
        }

        $result = $this->transactions->run(function () use ($ownerId): DeletionResult {
            $result = $this->repository->deleteOwnerData($ownerId);
            $this->supplementalEraser->erase($ownerId);

            return $result;
        });

        if (! $result instanceof DeletionResult) {
            throw new \RuntimeException('The deletion transaction returned an invalid result.');
        }

        return $result;
    }
}
