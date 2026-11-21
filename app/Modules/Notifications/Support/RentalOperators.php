<?php

namespace App\Modules\Notifications\Support;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Collection;

/**
 * RN-60 / D-72: who operates a rental. The owner of a self-managed property, or every active
 * administrator for a managed one.
 */
final class RentalOperators
{
    /**
     * @return Collection<int, User>
     */
    public static function of(Rental $rental): Collection
    {
        if ($rental->management_mode === ManagementMode::SelfManaged) {
            return collect([$rental->property->owner]);
        }

        return User::role(Role::Admin->value)->whereNull('suspended_at')->get()->toBase();
    }
}
