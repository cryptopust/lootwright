<?php

namespace App\Modules\Analysis\Infrastructure;

use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Edition router; each engine owns its own rules and provenance. */
final readonly class ProductionEditionDeterministicAnalysisEngine implements DeterministicAnalysisEngine
{
    public function __construct(private ProductionPoe1DeterministicAnalysisEngine $poe1, private ProductionPoe2DeterministicAnalysisEngine $poe2) {}

    public function resolve(AnalysisRecord $analysis, ArtifactRecord $artifact): ResolvedAnalysisContext
    {
        return $analysis->edition === GameEdition::Poe1 ? $this->poe1->resolve($analysis, $artifact) : $this->poe2->resolve($analysis, $artifact);
    }

    public function run(AnalysisRecord $analysis, ArtifactRecord $artifact, ResolvedAnalysisContext $context): DeterministicAnalysisSnapshot
    {
        return $analysis->edition === GameEdition::Poe1 ? $this->poe1->run($analysis, $artifact, $context) : $this->poe2->run($analysis, $artifact, $context);
    }
}
