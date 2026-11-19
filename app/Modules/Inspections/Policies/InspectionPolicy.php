<?php

namespace App\Modules\Inspections\Policies;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Inspections\Models\Inspection;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::InspectionsAssign->value);
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return $user->can(Permission::InspectionsAssign->value)
            || ($user->can(Permission::InspectionsPerform->value) && $inspection->inspector_id === $user->id)
            || ($user->can(Permission::InspectionsView->value) && $inspection->property->owner_id === $user->id);
    }

    public function assign(User $user, Inspection $inspection): bool
    {
        return $user->can(Permission::InspectionsAssign->value);
    }

    public function perform(User $user, Inspection $inspection): bool
    {
        return $user->can(Permission::InspectionsPerform->value) && $inspection->inspector_id === $user->id;
    }
}
