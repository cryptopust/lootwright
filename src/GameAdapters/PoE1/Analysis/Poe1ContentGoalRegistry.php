<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

use Lootwright\Domain\Analysis\ContentGoalRegistry;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class Poe1ContentGoalRegistry implements ContentGoalRegistry
{
    public const GOALS = ['mapping', 'bossing', 'delve', 'simulacrum', 'sanctum', 'progression'];

    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    public function supports(ContentGoal $goal): bool
    {
        return $goal->belongsTo(GameEdition::Poe1) && in_array($goal->value, self::GOALS, true);
    }

    public function identifiers(): array
    {
        return self::GOALS;
    }
}
