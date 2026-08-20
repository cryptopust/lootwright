<?php

namespace Lootwright\Domain\PoeCatalog\Character;

enum ProgressionKind: string
{
    case Ascendancy = 'ascendancy';
    case SecondaryProgression = 'secondary_progression';
}
