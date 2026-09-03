<?php

namespace Lootwright\GameAdapters\PoE2\BuildImport;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\BuildImport\AbstractEditionBuildImporter;

final readonly class Poe2BuildImporter extends AbstractEditionBuildImporter
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }
}
