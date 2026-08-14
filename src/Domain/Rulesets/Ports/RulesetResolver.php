<?php

namespace Lootwright\Domain\Rulesets\Ports;

use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;

interface RulesetResolver
{
    public function resolve(
        GameEdition $edition,
        PatchVersion $patch,
        ?LeagueId $league,
        ParserVersion $parserVersion,
    ): DomainResult;
}
