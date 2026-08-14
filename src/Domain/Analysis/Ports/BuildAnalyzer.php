<?php

namespace Lootwright\Domain\Analysis\Ports;

use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Identity\AnalysisId;

interface BuildAnalyzer
{
    public function analyze(
        AnalysisId $analysisId,
        CanonicalBuild $build,
        BuildIntent $intent,
    ): DomainResult;
}
