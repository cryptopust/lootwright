<?php

namespace Lootwright\Domain\PoeCatalog\Character;

enum Availability: string
{
    case Available = 'available';
    case Planned = 'planned';
    case Disabled = 'disabled';
    case Historical = 'historical';
}
