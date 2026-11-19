<?php

namespace App\Modules\Verification\Actions;

use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationExpired;
use App\Modules\Verification\Models\VerificationProfile;
use Carbon\CarbonImmutable;

/**
 * HU-02.05 · RN-04: runs daily. Expired clients keep their current rentals but cannot
 * request new ones until they renew. Idempotent.
 */
class ExpireVerifications
{
    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $expired = 0;

        VerificationProfile::query()
            ->where('status', VerificationStatus::Verified)
            ->whereNotNull('verified_until')
            ->whereDate('verified_until', '<', $today)
            ->each(function (VerificationProfile $profile) use (&$expired) {
                $profile->status->assertCanTransitionTo(VerificationStatus::Expired);
                $profile->update(['status' => VerificationStatus::Expired]);

                VerificationExpired::dispatch($profile);
                $expired++;
            });

        return $expired;
    }
}
