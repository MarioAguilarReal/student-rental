<?php

namespace App\Modules\Identity\Policies;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyPermission([Permission::UsersSuspend->value, Permission::UsersRolesAssign->value]);
    }

    public function view(User $actor, User $user): bool
    {
        return $this->viewAny($actor);
    }

    public function assignRoles(User $actor, User $user): bool
    {
        return $actor->can(Permission::UsersRolesAssign->value);
    }

    public function suspend(User $actor, User $user): bool
    {
        return $actor->can(Permission::UsersSuspend->value);
    }
}
