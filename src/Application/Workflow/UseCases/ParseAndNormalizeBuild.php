<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\ArtifactParser;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Application\Workflow\Ports\TransactionManager;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;

final readonly class ParseAndNormalizeBuild
{
    public function __construct(
        private WorkflowRepository $repository,
        private ArtifactStorage $storage,
        private ArtifactParser $parser,
        private DeterministicAnalysisEngine $engine,
        private WorkflowDispatcher $dispatcher,
        private RequestClarification $clarifications,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $artifactId): void
    {
        $artifact = $this->repository->claimArtifact($artifactId);

        if ($artifact === null) {
            return;
        }

        try {
            $contents = $this->storage->get($artifact->blobKey);
            $parsed = $this->parser->parse($artifact->artifactType, $contents, $artifact->edition);
            $this->guardParsedArtifact($artifact->edition->value, $parsed);
            $this->transactions->run(function () use ($artifactId, $parsed, $artifact): void {
                $this->repository->saveParsedArtifact($artifactId, $parsed);

                if ($parsed->clarifications !== []) {
                    $this->clarifications->handle($artifact->analysisId, $parsed->clarifications);

                    return;
                }

                $rulesetChecksum = $this->selectedRulesetChecksum($artifact->analysisId);
                if ($rulesetChecksum === null) {
                    $analysis = $this->repository->analysis($artifact->analysisId);
                    $parsedArtifact = $this->repository->artifact($artifactId);
                    if ($analysis === null || $parsedArtifact === null) {
                        throw new TerminalWorkflowFailure('analysis_identity_missing', 'The parsed analysis identity could not be resolved.');
                    }

                    $context = $this->engine->resolve($analysis, $parsedArtifact);
                    $this->repository->pinAnalysisRuleset($artifact->analysisId, $context);
                    $rulesetChecksum = $context->rulesetChecksumSha256;
                }

                $this->repository->transitionAnalysis($artifact->analysisId, AnalysisState::Queued);
                $this->dispatcher->analyze(
                    $artifact->analysisId,
                    $artifact->edition,
                    $rulesetChecksum,
                );
            });
            $this->deleteRawArtifact($artifactId, $artifact->blobKey);
        } catch (PolicyBlocked $exception) {
            $this->repository->failArtifact($artifactId, AnalysisState::PolicyBlocked, $exception->decision->reason->value);
            $this->deleteRawArtifact($artifactId, $artifact->blobKey);
        } catch (TerminalWorkflowFailure $exception) {
            $this->repository->failArtifact($artifactId, AnalysisState::Failed, $exception->failureCode);
            $this->deleteRawArtifact($artifactId, $artifact->blobKey);
        } catch (TransientWorkflowFailure $exception) {
            $this->repository->requeueArtifact($artifactId);

            throw $exception;
        }
    }

    private function selectedRulesetChecksum(string $analysisId): ?string
    {
        $analysis = $this->repository->analysis($analysisId);
        $parameters = $analysis === null ? null : json_decode($analysis->parametersSnapshot, true);
        $checksum = is_array($parameters) && is_array($parameters['selection'] ?? null)
            ? ($parameters['selection']['ruleset_checksum_sha256'] ?? null)
            : null;

        return is_string($checksum) ? $checksum : null;
    }

    private function deleteRawArtifact(string $artifactId, string $blobKey): void
    {
        try {
            $this->storage->delete($blobKey);
            $this->repository->markArtifactBlobDeleted($artifactId);
        } catch (TransientWorkflowFailure) {
            // The hourly expiry workflow retries deletion by raw_expires_at.
        }
    }

    private function guardParsedArtifact(string $expectedEdition, ParsedArtifact $parsed): void
    {
        if ($parsed->edition->value !== $expectedEdition
            || preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $parsed->adapterKey) !== 1
            || preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9a-z.-]+)?$/D', $parsed->parserVersion) !== 1
            || $parsed->normalizedSnapshot === ''
            || preg_match('/^[0-9a-f]{64}$/D', $parsed->normalizedHashSha256) !== 1
            || ! hash_equals($parsed->normalizedHashSha256, hash('sha256', $parsed->normalizedSnapshot))
        ) {
            throw new TerminalWorkflowFailure(
                'normalized_snapshot_identity_mismatch',
                'The normalized artifact failed its game, adapter, parser, or checksum invariant.',
            );
        }
    }
}
