<?php

namespace Lootwright\Domain\PoeCatalog\Item;

enum ItemRarity: string
{
    case Normal = 'normal';
    case Magic = 'magic';
    case Rare = 'rare';
    case Unique = 'unique';
}
