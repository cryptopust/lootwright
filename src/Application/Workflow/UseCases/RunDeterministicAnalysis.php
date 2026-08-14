<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\AnalysisPolicyGate;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;

final readonly class RunDeterministicAnalysis
{
    public function __construct(
        private WorkflowRepository $repository,
        private DeterministicAnalysisEngine $engine,
        private AnalysisPolicyGate $policy,
    ) {}

    public function handle(string $analysisId): void
    {
        $analysis = $this->repository->claimAnalysis($analysisId);

        if ($analysis === null) {
            return;
        }

        $artifact = $this->repository->artifact($analysis->artifactId);

        if ($artifact === null || $artifact->normalizedSnapshot === null) {
            $this->repository->transitionAnalysis($analysisId, AnalysisState::Failed, failureCode: 'normalized_artifact_missing');

            return;
        }

        try {
            $context = $this->engine->resolve($analysis, $artifact);
            $this->guardContext($artifact, $context);
            $this->policy->authorize($context);
            $snapshot = $this->engine->run($analysis, $artifact, $context);
            $this->guardSnapshot($snapshot->inputSnapshot, $snapshot->inputHashSha256);
            $this->guardSnapshot($snapshot->outputSnapshot, $snapshot->outputHashSha256);

            if ($snapshot->adapterKey !== $artifact->adapterKey
                || $snapshot->parserVersion !== $artifact->parserVersion
                || $snapshot->rulesetId !== $context->rulesetId
                || $snapshot->rulesetVersion !== $context->rulesetVersion
                || $snapshot->rulesetChecksumSha256 !== $context->rulesetChecksumSha256
            ) {
                throw new TerminalWorkflowFailure('analysis_identity_mismatch', 'Analysis identities changed after exact resolution.');
            }

            $this->repository->completeAnalysis($analysisId, $snapshot);
        } catch (PolicyBlocked $exception) {
            $this->repository->transitionAnalysis(
                $analysisId,
                AnalysisState::PolicyBlocked,
                failureCode: $exception->decision->reason->value,
            );
        } catch (TerminalWorkflowFailure $exception) {
            $this->repository->transitionAnalysis($analysisId, AnalysisState::Failed, failureCode: $exception->failureCode);
        } catch (TransientWorkflowFailure $exception) {
            $this->repository->requeueAnalysis($analysisId);

            throw $exception;
        }
    }

    private function guardSnapshot(string $snapshot, string $hash): void
    {
        if ($snapshot === '' || preg_match('/^[0-9a-f]{64}$/D', $hash) !== 1 || ! hash_equals($hash, hash('sha256', $snapshot))) {
            throw new TerminalWorkflowFailure('snapshot_hash_mismatch', 'An immutable analysis snapshot failed checksum verification.');
        }
    }

    private function guardContext(
        ArtifactRecord $artifact,
        ResolvedAnalysisContext $context,
    ): void {
        if ($artifact->adapterKey !== $context->adapterKey
            || $artifact->parserVersion !== $context->parserVersion
            || preg_match('/^[0-9a-f]{64}$/D', $context->rulesetChecksumSha256) !== 1
            || preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $context->sourceId) !== 1
            || trim($context->sourceVersion) === ''
            || trim($context->rulesetId) === ''
            || trim($context->rulesetVersion) === ''
        ) {
            throw new TerminalWorkflowFailure(
                'analysis_resolution_identity_mismatch',
                'The resolved adapter, parser, ruleset, or provenance identity is invalid.',
            );
        }
    }
}
