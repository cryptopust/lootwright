<?php

namespace Lootwright\Domain\Rulesets\Ports;

use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Shared\Game\GameEdition;

interface RulesetRepository
{
    public function findById(string $id): ?GameRuleset;

    public function findByVersion(GameEdition $edition, string $version): ?GameRuleset;
}
