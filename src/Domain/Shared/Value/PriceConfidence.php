<?php

namespace Lootwright\Domain\Shared\Value;

enum PriceConfidence: string
{
    case Unknown = 'unknown';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
