<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\InternalRolesChanged;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;

/**
 * HU-01.03: a Super Admin grants or revokes internal roles. Client/owner roles are
 * left untouched. A Super Admin cannot remove their own Super Admin role (lock-out guard).
 */
class SyncInternalRoles
{
    /**
     * @param  list<Role>  $roles
     */
    public function handle(User $actor, User $user, array $roles): void
    {
        $internal = array_values(array_filter(Role::cases(), fn (Role $role): bool => ! in_array($role, Role::selfAssignable(), true)));
        $requested = array_values(array_filter($roles, fn (Role $role): bool => in_array($role, $internal, true)));

        if ($actor->is($user) && $user->hasRole(Role::SuperAdmin->value) && ! in_array(Role::SuperAdmin, $requested, true)) {
            throw new BusinessRuleViolation('HU-01.03', __('You cannot remove your own Super Admin role.'), 'roles');
        }

        $before = Role::valuesOf($user);

        foreach ($internal as $role) {
            in_array($role, $requested, true)
                ? $user->assignRole($role->value)
                : $user->removeRole($role->value);
        }

        $after = Role::valuesOf($user->load('roles'));

        if ($before !== $after) {
            InternalRolesChanged::dispatch($user, $actor, $before, $after);
        }
    }
}
