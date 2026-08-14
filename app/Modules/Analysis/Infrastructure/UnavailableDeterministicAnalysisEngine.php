<?php

namespace App\Modules\Analysis\Infrastructure;

use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;

final class UnavailableDeterministicAnalysisEngine implements DeterministicAnalysisEngine
{
    public function resolve(AnalysisRecord $analysis, ArtifactRecord $artifact): ResolvedAnalysisContext
    {
        throw new TerminalWorkflowFailure(
            'exact_ruleset_unavailable',
            'No approved immutable ruleset is active for deterministic analysis.',
        );
    }

    public function run(
        AnalysisRecord $analysis,
        ArtifactRecord $artifact,
        ResolvedAnalysisContext $context,
    ): DeterministicAnalysisSnapshot {
        throw new TerminalWorkflowFailure(
            'deterministic_analyzer_unavailable',
            'No approved deterministic analyzer is active.',
        );
    }
}
