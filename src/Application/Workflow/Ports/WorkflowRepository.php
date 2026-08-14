<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeletionResult;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Application\Workflow\DTO\SubmissionReceipt;
use Lootwright\Domain\Shared\Game\GameEdition;

interface WorkflowRepository
{
    public function submit(
        string $artifactId,
        string $analysisId,
        string $ownerId,
        string $idempotencyKey,
        GameEdition $edition,
        string $locale,
        string $artifactType,
        string $blobKey,
        string $artifactHashSha256,
        int $artifactBytes,
        string $parametersSnapshot,
        string $parametersHashSha256,
    ): SubmissionReceipt;

    public function claimArtifact(string $artifactId): ?ArtifactRecord;

    public function artifact(string $artifactId): ?ArtifactRecord;

    public function saveParsedArtifact(string $artifactId, ParsedArtifact $parsed): void;

    public function claimAnalysis(string $analysisId): ?AnalysisRecord;

    public function analysisForOwner(string $analysisId, string $ownerId): ?AnalysisRecord;

    public function analysis(string $analysisId): ?AnalysisRecord;

    public function completeAnalysis(string $analysisId, DeterministicAnalysisSnapshot $snapshot): void;

    public function transitionAnalysis(
        string $analysisId,
        AnalysisState $state,
        ?string $detailSnapshot = null,
        ?string $failureCode = null,
    ): void;

    public function requeueArtifact(string $artifactId): void;

    public function requeueAnalysis(string $analysisId): void;

    public function failArtifact(string $artifactId, AnalysisState $state, string $failureCode): void;

    public function markArtifactBlobDeleted(string $artifactId): void;

    /** @return list<ArtifactRecord> */
    public function expiredArtifacts(): array;

    public function expireArtifact(string $artifactId): void;

    public function createAnalysisVersion(
        string $analysisId,
        AnalysisRecord $parent,
        string $parametersSnapshot,
        string $parametersHashSha256,
    ): AnalysisRecord;

    /** @return list<string> */
    public function ownerArtifactBlobKeys(string $ownerId): array;

    public function deleteOwnerData(string $ownerId): DeletionResult;
}
