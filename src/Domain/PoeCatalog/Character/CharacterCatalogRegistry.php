<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use Lootwright\Domain\Shared\Game\GameEdition;

final class CharacterCatalogRegistry
{
    public static function for(GameEdition $edition): CharacterCatalog
    {
        return match ($edition) {
            GameEdition::Poe1 => Poe1CharacterCatalog::current(),
            GameEdition::Poe2 => Poe2CharacterCatalog::current(),
        };
    }
}
