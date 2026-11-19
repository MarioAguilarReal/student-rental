<?php

namespace App\Modules\Verification\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;

/**
 * HU-01.01: every client or owner gets a verification profile when the role is added.
 * Idempotent: returns the existing profile of that kind if there is one.
 */
class OpenVerificationProfile
{
    public function handle(User $user, VerificationKind $kind): VerificationProfile
    {
        $existing = $user->verificationProfiles()->where('kind', $kind)->first();

        if ($existing) {
            return $existing;
        }

        $emailVerified = $user->hasVerifiedEmail();

        $profile = $user->verificationProfiles()->create([
            'kind' => $kind,
            'status' => $emailVerified ? VerificationStatus::EmailVerified : VerificationStatus::Unverified,
            'institution_id' => $emailVerified && $kind === VerificationKind::Client
                ? Institution::matchingEmail($user->email)?->id
                : null,
        ]);

        $user->unsetRelation('verificationProfiles');

        return $profile;
    }
}
