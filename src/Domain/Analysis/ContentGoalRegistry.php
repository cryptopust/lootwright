<?php

namespace Lootwright\Domain\Analysis;

use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\Shared\Game\GameEdition;

interface ContentGoalRegistry
{
    public function edition(): GameEdition;

    public function supports(ContentGoal $goal): bool;

    /** @return list<string> */
    public function identifiers(): array;
}
