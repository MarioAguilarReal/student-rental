<?php

namespace App\Modules\Verification\Actions;

use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationExpiringSoon;
use App\Modules\Verification\Models\VerificationProfile;
use Carbon\CarbonImmutable;

/**
 * HU-02.05 · RN-04: one reminder, 15 days before the verification expires. Runs daily.
 */
class SendExpiryReminders
{
    public const DAYS_BEFORE = 15;

    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $reminded = 0;

        VerificationProfile::query()
            ->where('status', VerificationStatus::Verified)
            ->whereNull('expiry_reminder_sent_at')
            ->whereNotNull('verified_until')
            ->whereDate('verified_until', '>=', $today)
            ->whereDate('verified_until', '<=', $today->addDays(self::DAYS_BEFORE))
            ->each(function (VerificationProfile $profile) use (&$reminded) {
                $profile->update(['expiry_reminder_sent_at' => now()]);

                VerificationExpiringSoon::dispatch($profile);
                $reminded++;
            });

        return $reminded;
    }
}
