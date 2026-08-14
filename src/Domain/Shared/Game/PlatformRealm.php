<?php

namespace Lootwright\Domain\Shared\Game;

enum PlatformRealm: string
{
    case Pc = 'pc';
    case Xbox = 'xbox';
    case Sony = 'sony';
    case Poe2 = 'poe2';

    public function supports(GameEdition $edition): bool
    {
        return match ($edition) {
            GameEdition::Poe1 => $this !== self::Poe2,
            GameEdition::Poe2 => $this === self::Poe2,
        };
    }
}
