<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\UserReactivated;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;

/**
 * HU-01.04: lifts a suspension. Same authority rules as suspending.
 */
class ReactivateUser
{
    public function handle(User $actor, User $user): void
    {
        if (! $user->isSuspended()) {
            throw new BusinessRuleViolation('HU-01.04', __('This account is not suspended.'));
        }

        if ($user->hasAnyRole([Role::SuperAdmin->value, Role::Admin->value]) && ! $actor->hasRole(Role::SuperAdmin->value)) {
            throw new BusinessRuleViolation('HU-01.04', __('Only a Super Admin can reactivate administrators.'));
        }

        $user->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();

        UserReactivated::dispatch($user, $actor);
    }
}
