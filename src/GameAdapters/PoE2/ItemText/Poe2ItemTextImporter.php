<?php

namespace Lootwright\GameAdapters\PoE2\ItemText;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\Shared\ItemText\AbstractItemTextImporter;

final class Poe2ItemTextImporter extends AbstractItemTextImporter
{
    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    protected function beta(): bool
    {
        return true;
    }
}
