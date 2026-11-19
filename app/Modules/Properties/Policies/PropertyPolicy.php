<?php

namespace App\Modules\Properties\Policies;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Models\Property;

/**
 * Owners manage their own properties; administrators see every property and act as
 * operator of the managed ones (HU-04.09, RN-60).
 */
class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([Permission::PropertiesCreate->value, Permission::PropertiesViewAny->value]);
    }

    public function viewAll(User $user): bool
    {
        return $user->can(Permission::PropertiesViewAny->value);
    }

    public function view(User $user, Property $property): bool
    {
        return $property->owner_id === $user->id || $user->can(Permission::PropertiesViewAny->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PropertiesCreate->value);
    }

    public function createOnBehalf(User $user): bool
    {
        return $user->hasAllPermissions([Permission::PropertiesCreate->value, Permission::PropertiesViewAny->value]);
    }

    public function update(User $user, Property $property): bool
    {
        return $this->operates($user, $property, Permission::PropertiesUpdate);
    }

    public function publish(User $user, Property $property): bool
    {
        return $this->operates($user, $property, Permission::PropertiesPublish);
    }

    public function archive(User $user, Property $property): bool
    {
        return $this->operates($user, $property, Permission::PropertiesArchive);
    }

    public function requestInspection(User $user, Property $property): bool
    {
        return $this->operates($user, $property, Permission::InspectionsRequest);
    }

    private function operates(User $user, Property $property, Permission $permission): bool
    {
        if (! $user->can($permission->value)) {
            return false;
        }

        return $property->owner_id === $user->id
            || ($property->management_mode === ManagementMode::Managed && $user->can(Permission::PropertiesViewAny->value));
    }
}
