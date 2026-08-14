<?php

namespace Lootwright\Application\TradePlanning\DTO;

enum RecipeFilterMode: string
{
    case Required = 'required';
    case Weighted = 'weighted';
    case Excluded = 'excluded';
    case Omitted = 'omitted';
}
