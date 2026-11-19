<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Verification\Actions\OpenVerificationProfile;
use App\Modules\Verification\Enums\VerificationKind;

/**
 * HU-01.01: a user can be client, owner or both. Internal roles are never self-assigned (D-10).
 */
class AddAccountProfile
{
    public function __construct(private OpenVerificationProfile $openVerificationProfile) {}

    public function handle(User $user, Role $role): void
    {
        $kind = VerificationKind::forRole($role);

        if (! in_array($role, Role::selfAssignable(), true) || $kind === null) {
            throw new BusinessRuleViolation('D-10', __('That account type cannot be added by yourself.'), 'role');
        }

        $user->assignRole($role->value);
        $this->openVerificationProfile->handle($user, $kind);
    }
}
