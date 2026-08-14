<?php

namespace Lootwright\Domain\PolicyProvenance;

enum CommercialUseStatus: string
{
    case Unknown = 'unknown';
    case NonCommercialOnly = 'non_commercial_only';
    case Allowed = 'allowed';
    case Prohibited = 'prohibited';
}
