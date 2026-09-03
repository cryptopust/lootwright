<?php

namespace Lootwright\GameAdapters\PoE1\GameData;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\GameData\AbstractGameDataNormalizer;

final class Poe1GameDataNormalizer extends AbstractGameDataNormalizer
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    protected function schemaVersion(): string
    {
        return 'lootwright.poe1.game-data.v1';
    }
}
