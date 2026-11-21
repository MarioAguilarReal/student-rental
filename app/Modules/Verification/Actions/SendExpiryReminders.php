<?php

namespace App\Modules\Verification\Actions;

use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationExpiringSoon;
use App\Modules\Verification\Models\VerificationProfile;
use Carbon\CarbonImmutable;

/**
 * HU-02.05 · RN-04: one reminder, 15 days (HU-03.05) before the verification expires. Runs daily.
 */
class SendExpiryReminders
{
    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $reminded = 0;

        VerificationProfile::query()
            ->where('status', VerificationStatus::Verified)
            ->whereNull('expiry_reminder_sent_at')
            ->whereNotNull('verified_until')
            ->whereDate('verified_until', '>=', $today)
            ->whereDate('verified_until', '<=', $today->addDays(Parameters::int(SystemParameter::VerificationReminderDays)))
            ->each(function (VerificationProfile $profile) use (&$reminded) {
                $profile->update(['expiry_reminder_sent_at' => now()]);

                VerificationExpiringSoon::dispatch($profile);
                $reminded++;
            });

        return $reminded;
    }
}
