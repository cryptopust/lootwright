<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
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
            $context = $this->withAnalysisIdentity($context, $analysis->id);
            $this->guardContext($artifact, $context);
            $this->guardSelection($analysis, $context);
            $this->policy->authorize($context);
            $snapshot = $this->engine->run($analysis, $artifact, $context);
            $snapshot = $this->withContextSnapshot($snapshot, $context);
            $this->guardSnapshot($snapshot->inputSnapshot, $snapshot->inputHashSha256);
            $this->guardSnapshot($snapshot->outputSnapshot, $snapshot->outputHashSha256);

            if ($snapshot->adapterKey !== $artifact->adapterKey
                || $snapshot->parserVersion !== $artifact->parserVersion
                || $snapshot->rulesetId !== $context->rulesetId
                || $snapshot->rulesetVersion !== $context->rulesetVersion
                || $snapshot->rulesetChecksumSha256 !== $context->rulesetChecksumSha256
                || ($snapshot->sourceId !== null && $snapshot->sourceId !== $context->sourceId)
                || ($snapshot->sourceVersion !== null && $snapshot->sourceVersion !== $context->sourceVersion)
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

    private function guardSelection(AnalysisRecord $analysis, ResolvedAnalysisContext $context): void
    {
        $parameters = json_decode($analysis->parametersSnapshot, true);
        $selection = is_array($parameters) && is_array($parameters['selection'] ?? null)
            ? $parameters['selection']
            : null;

        if ($selection === null) {
            return;
        }

        $selectedId = $selection['ruleset_id'] ?? null;
        $selectedVersion = $selection['ruleset_version'] ?? null;
        $selectedChecksum = $selection['ruleset_checksum_sha256'] ?? null;
        $selectedLeague = $selection['league'] ?? null;

        if (($selectedId !== null && $selectedId !== $context->rulesetId)
            || ($selectedVersion !== null && $selectedVersion !== $context->rulesetVersion)
            || ($selectedChecksum !== null && $selectedChecksum !== $context->rulesetChecksumSha256)
            || ($selectedLeague !== null && $selectedLeague !== $context->league)
        ) {
            throw new TerminalWorkflowFailure(
                'stale_ruleset_selection',
                'The resolved ruleset or league no longer matches the immutable user selection.',
            );
        }
    }

    private function withAnalysisIdentity(ResolvedAnalysisContext $context, string $analysisId): ResolvedAnalysisContext
    {
        return new ResolvedAnalysisContext(
            $context->adapterKey,
            $context->parserVersion,
            $context->rulesetId,
            $context->rulesetVersion,
            $context->rulesetChecksumSha256,
            $context->sourceId,
            $context->sourceVersion,
            $context->patchVersion,
            $context->league,
            $analysisId,
        );
    }

    private function withContextSnapshot(
        DeterministicAnalysisSnapshot $snapshot,
        ResolvedAnalysisContext $context,
    ): DeterministicAnalysisSnapshot {
        return new DeterministicAnalysisSnapshot(
            $snapshot->adapterKey,
            $snapshot->parserVersion,
            $snapshot->rulesetId,
            $snapshot->rulesetVersion,
            $snapshot->rulesetChecksumSha256,
            $snapshot->inputSnapshot,
            $snapshot->inputHashSha256,
            $snapshot->outputSnapshot,
            $snapshot->outputHashSha256,
            $snapshot->findings,
            $snapshot->recommendations,
            $snapshot->recipes,
            $context->sourceId,
            $context->sourceVersion,
            $context->patchVersion,
            $context->league,
        );
    }
}
