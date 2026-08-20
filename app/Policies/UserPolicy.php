<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserRole;

final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isAdmin() && ($actor->isSuperAdmin() || $target->role === UserRole::Member || $actor->is($target));
    }

    public function suspend(User $actor, User $target): bool
    {
        return $actor->isAdmin() && ! $actor->is($target) && ($actor->isSuperAdmin() || $target->role === UserRole::Member);
    }

    public function changeRole(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && ! $actor->is($target);
    }
}
