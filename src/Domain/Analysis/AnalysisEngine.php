<?php

namespace Lootwright\Domain\Analysis;

use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Shared\Error\DomainResult;

interface AnalysisEngine
{
    /**
     * Analyze a normalized build. A bare BuildSnapshot is accepted for shared
     * API compatibility; adapters must return unsupported when no canonical
     * facts are attached rather than inventing them.
     */
    public function analyze(
        BuildSnapshot|CanonicalImportedBuild|CanonicalBuild $build,
        BuildIntent $intent,
        GameRuleset $ruleset,
    ): DomainResult;
}
