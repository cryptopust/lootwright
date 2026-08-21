<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use Lootwright\Domain\Analysis\ContentGoalRegistry;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\Shared\Game\GameEdition;

/** No goal is enabled until it is verified against an approved PoE2 ruleset. */
final readonly class Poe2ContentGoalRegistry implements ContentGoalRegistry
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    public function supports(ContentGoal $goal): bool
    {
        return false;
    }

    public function identifiers(): array
    {
        return [];
    }
}
