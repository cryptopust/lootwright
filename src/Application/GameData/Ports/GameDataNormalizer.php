<?php

namespace Lootwright\Application\GameData\Ports;

use Lootwright\Application\GameData\DTO\GameDataSourceDocument;
use Lootwright\Application\GameData\DTO\NormalizedGameDataset;
use Lootwright\Domain\Shared\Game\GameEdition;

interface GameDataNormalizer
{
    public function edition(): GameEdition;

    public function normalize(GameDataSourceDocument $document): NormalizedGameDataset;
}
