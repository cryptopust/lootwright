<?php

namespace App\Models;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case PendingDeletion = 'pending_deletion';
}
