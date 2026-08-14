<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\SubmissionReceipt;
use Lootwright\Application\Workflow\DTO\SubmitBuildArtifactCommand;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;
use Lootwright\Application\Workflow\Ports\TransactionManager;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Throwable;

final readonly class SubmitBuildArtifact
{
    public function __construct(
        private WorkflowRepository $repository,
        private ArtifactStorage $storage,
        private WorkflowDispatcher $dispatcher,
        private IdentifierGenerator $identifiers,
        private TransactionManager $transactions,
    ) {}

    public function handle(SubmitBuildArtifactCommand $command): SubmissionReceipt
    {
        $this->validate($command);
        $artifactId = $this->identifiers->uuid7();
        $analysisId = $this->identifiers->uuid7();
        $blobKey = 'build-artifacts/'.$artifactId.'.payload';
        $artifactHash = hash('sha256', $command->artifact);
        $parameters = $command->parameters->canonicalJson();
        $stored = false;

        try {
            $receipt = $this->transactions->run(function () use (
                $command,
                $artifactId,
                $analysisId,
                $blobKey,
                $artifactHash,
                $parameters,
                &$stored,
            ): SubmissionReceipt {
                $receipt = $this->repository->submit(
                    $artifactId,
                    $analysisId,
                    $command->ownerId,
                    $command->idempotencyKey,
                    $command->edition,
                    $command->locale->value,
                    $command->artifactType,
                    $blobKey,
                    $artifactHash,
                    strlen($command->artifact),
                    $parameters,
                    hash('sha256', $parameters),
                );

                if (! $receipt->replayed) {
                    $this->storage->put($blobKey, $command->artifact);
                    $stored = true;
                }

                return $receipt;
            });
        } catch (Throwable $exception) {
            if ($stored) {
                $this->storage->delete($blobKey);
            }

            throw $exception;
        }

        if (! $receipt instanceof SubmissionReceipt) {
            throw new InvalidWorkflowInput('The submission transaction returned an invalid receipt.');
        }

        if (! $receipt->replayed) {
            $this->dispatcher->parse($receipt->artifactId);
        }

        return $receipt;
    }

    private function validate(SubmitBuildArtifactCommand $command): void
    {
        if (trim($command->ownerId) === ''
            || preg_match('/^[A-Za-z0-9._:-]{1,128}$/D', $command->ownerId) !== 1
            || preg_match('/^[A-Za-z0-9._:-]{32,128}$/D', $command->idempotencyKey) !== 1
            || $command->artifactType !== 'pob'
            || $command->artifact === ''
            || strlen($command->artifact) > 1_048_576
        ) {
            throw new InvalidWorkflowInput('The build submission metadata or artifact is invalid.');
        }
    }
}
