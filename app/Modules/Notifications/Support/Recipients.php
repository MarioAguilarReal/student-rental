<?php

namespace App\Modules\Notifications\Support;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Collection;

/**
 * Who receives what (HU-10.01).
 */
final class Recipients
{
    /**
     * RN-60 / D-72: the owner of a self-managed property, or every active administrator.
     *
     * @return Collection<int, User>
     */
    public static function operatorsOf(Rental $rental): Collection
    {
        if ($rental->management_mode === ManagementMode::SelfManaged) {
            return collect([$rental->property->owner]);
        }

        return self::admins();
    }

    /**
     * @return Collection<int, User>
     */
    public static function admins(): Collection
    {
        return User::role(Role::Admin->value)->whereNull('suspended_at')->get()->toBase();
    }
}
