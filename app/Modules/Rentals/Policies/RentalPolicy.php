<?php

namespace App\Modules\Rentals\Policies;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Rentals\Models\Rental;

/**
 * RN-60 and docs/spec/02-roles-permisos.md: the operator decides (the owner of a self-managed
 * property, an administrator of a managed one); the owner of a managed property only reads;
 * only administrators cancel (RN-66). The rental's own copy of the mode counts (RN-14).
 */
class RentalPolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::RentalsRequest->value);
    }

    /**
     * HU-08.05: owners see the rentals of their properties; administrators see all of them.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RentalsView->value)
            && $user->hasAnyPermission([Permission::PropertiesCreate->value, Permission::PropertiesViewAny->value]);
    }

    public function viewAll(User $user): bool
    {
        return $user->hasAllPermissions([Permission::RentalsView->value, Permission::PropertiesViewAny->value]);
    }

    public function view(User $user, Rental $rental): bool
    {
        if (! $user->can(Permission::RentalsView->value)) {
            return false;
        }

        return $rental->client_id === $user->id
            || $rental->property->owner_id === $user->id
            || $user->can(Permission::PropertiesViewAny->value);
    }

    public function decide(User $user, Rental $rental): bool
    {
        if (! $user->can(Permission::RentalsDecide->value)) {
            return false;
        }

        return $rental->management_mode === ManagementMode::Managed
            ? $user->can(Permission::PropertiesViewAny->value)
            : $rental->property->owner_id === $user->id;
    }

    public function cancel(User $user, Rental $rental): bool
    {
        return $user->can(Permission::RentalsCancel->value);
    }
}
