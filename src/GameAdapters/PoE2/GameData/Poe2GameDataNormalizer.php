<?php

namespace Lootwright\GameAdapters\PoE2\GameData;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\GameData\AbstractGameDataNormalizer;

final class Poe2GameDataNormalizer extends AbstractGameDataNormalizer
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    protected function schemaVersion(): string
    {
        return 'lootwright.poe2.game-data.v1';
    }
}
