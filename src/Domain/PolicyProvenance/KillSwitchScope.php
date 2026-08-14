<?php

namespace Lootwright\Domain\PolicyProvenance;

enum KillSwitchScope: string
{
    case Global = 'global';
    case Source = 'source';
    case Capability = 'capability';
    case SourceCapability = 'source_capability';
}
