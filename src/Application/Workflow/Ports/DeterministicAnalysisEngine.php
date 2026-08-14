<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;

interface DeterministicAnalysisEngine
{
    /**
     * Resolve one exact game adapter and immutable ruleset before policy is
     * evaluated. Resolution must verify the ruleset checksum and provenance.
     */
    public function resolve(AnalysisRecord $analysis, ArtifactRecord $artifact): ResolvedAnalysisContext;

    /** Run pure domain ports and return byte-stable canonical snapshots. */
    public function run(
        AnalysisRecord $analysis,
        ArtifactRecord $artifact,
        ResolvedAnalysisContext $context,
    ): DeterministicAnalysisSnapshot;
}
