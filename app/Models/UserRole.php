<?php

namespace App\Models;

enum UserRole: string
{
    case Member = 'member';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';
}
