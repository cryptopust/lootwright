<?php

namespace Lootwright\Domain\Recommendations;

use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Shared\Game\GameEdition;

interface UpgradeCandidateFactory
{
    public function edition(): GameEdition;

    /** @return list<UpgradeCandidate> */
    public function create(AnalysisResult $analysis, BuildIntent $intent): array;
}
