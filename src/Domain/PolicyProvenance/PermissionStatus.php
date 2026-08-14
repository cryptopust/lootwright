<?php

namespace Lootwright\Domain\PolicyProvenance;

enum PermissionStatus: string
{
    case Denied = 'denied';
    case Conditional = 'conditional';
    case Allowed = 'allowed';
}
