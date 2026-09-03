<?php

namespace Lootwright\GameAdapters\PoE1\BuildImport;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\BuildImport\AbstractEditionBuildImporter;

final readonly class Poe1BuildImporter extends AbstractEditionBuildImporter
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }
}
