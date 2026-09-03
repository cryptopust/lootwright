<?php

namespace Lootwright\Domain\PoeCatalog\Character;

enum ProgressionKind: string
{
    case Regular = 'regular';
    case Alternate = 'alternate';
    case Secondary = 'secondary';
}
