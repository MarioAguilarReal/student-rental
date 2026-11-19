<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\UserSuspended;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * HU-01.04: suspension requires a reason and closes every active session.
 * Nobody suspends themselves; only a Super Admin can suspend staff (Admin/Super Admin).
 * Active rentals are not touched automatically.
 */
class SuspendUser
{
    public function handle(User $actor, User $user, string $reason): void
    {
        if ($actor->is($user)) {
            throw new BusinessRuleViolation('HU-01.04', __('You cannot suspend your own account.'), 'reason');
        }

        if ($user->hasAnyRole([Role::SuperAdmin->value, Role::Admin->value]) && ! $actor->hasRole(Role::SuperAdmin->value)) {
            throw new BusinessRuleViolation('HU-01.04', __('Only a Super Admin can suspend administrators.'), 'reason');
        }

        if ($user->isSuspended()) {
            throw new BusinessRuleViolation('HU-01.04', __('This account is already suspended.'), 'reason');
        }

        DB::transaction(function () use ($user, $reason) {
            $user->forceFill(['suspended_at' => now(), 'suspension_reason' => $reason])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill(['remember_token' => null])->save();
        });

        UserSuspended::dispatch($user, $actor, $reason);
    }
}
