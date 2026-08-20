<?php
namespace Lootwright\Application\ExternalSources\Ports;
use Lootwright\Application\ExternalSources\DTO\EconomyLeague;
interface EconomyLeagueProvider { /** @return list<EconomyLeague> */ public function leagues(): array; }
