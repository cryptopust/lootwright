<?php

namespace Lootwright\GameAdapters\PoE1\ItemText;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\ItemText\AbstractItemTextImporter;

final class Poe1ItemTextImporter extends AbstractItemTextImporter
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    protected function beta(): bool
    {
        return false;
    }
}
