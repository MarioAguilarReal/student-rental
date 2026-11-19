<?php

namespace App\Modules\Verification\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;

/**
 * HU-02.01 · RN-02: once the email is confirmed the profile can collect documents, and a
 * client whose email domain belongs to a registered institution is linked to it automatically.
 */
class MarkEmailVerified
{
    public function handle(User $user): void
    {
        foreach ($user->verificationProfiles as $profile) {
            if ($profile->status !== VerificationStatus::Unverified) {
                continue;
            }

            $profile->status->assertCanTransitionTo(VerificationStatus::EmailVerified);
            $profile->status = VerificationStatus::EmailVerified;

            if ($profile->kind === VerificationKind::Client && $profile->institution_id === null) {
                $profile->institution_id = Institution::matchingEmail($user->email)?->id;
            }

            $profile->save();
        }
    }
}
