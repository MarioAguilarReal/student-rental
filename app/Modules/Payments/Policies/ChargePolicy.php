<?php

namespace App\Modules\Payments\Policies;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Payments\Models\Charge;

/**
 * docs/spec/02-roles-permisos.md: a client pays only the charges of their own rentals.
 */
class ChargePolicy
{
    public function pay(User $user, Charge $charge): bool
    {
        return $user->can(Permission::ChargesPay->value) && $charge->rental->client_id === $user->id;
    }
}
