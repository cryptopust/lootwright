<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use Lootwright\Domain\Analysis\ContentGoalRegistry;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Goals supported by the PoE2 adapter; mechanics remain ruleset-scoped. */
final readonly class Poe2ContentGoalRegistry implements ContentGoalRegistry
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    public function supports(ContentGoal $goal): bool
    {
        return $goal->edition === GameEdition::Poe2 && in_array($goal->value, $this->identifiers(), true);
    }

    public function identifiers(): array
    {
        return ['progression', 'mapping', 'bossing'];
    }
}
