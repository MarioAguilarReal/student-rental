<?php

namespace App\Modules\Verification\Actions;

use App\Models\User;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Verification\Models\VerificationProfile;

/**
 * RN-08: conflict of interest. Shared by approval and rejection.
 */
class EnsureReviewerIsNotTheApplicant
{
    public static function check(User $reviewer, VerificationProfile $profile): void
    {
        (new self)->handle($reviewer, $profile);
    }

    public function handle(User $reviewer, VerificationProfile $profile): void
    {
        if ($profile->user_id === $reviewer->id) {
            throw new BusinessRuleViolation('RN-08', __('You cannot review your own verification.'));
        }
    }
}
