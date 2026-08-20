<?php

namespace Lootwright\Domain\Rulesets\Ports;

use Lootwright\Domain\Rulesets\RulesetResolution;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;

interface ActiveRulesetResolver
{
    public function resolveActive(
        GameEdition $edition,
        PatchVersion $patch,
        ?LeagueId $league,
        ParserVersion $parserVersion,
    ): RulesetResolution;
}
