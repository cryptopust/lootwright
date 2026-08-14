<?php

namespace Lootwright\Domain\PolicyProvenance;

enum PermissionStatus: string
{
    case Allowed = 'allowed';
    case Denied = 'denied';
    case Unknown = 'unknown';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Conflicting = 'conflicting';
}
