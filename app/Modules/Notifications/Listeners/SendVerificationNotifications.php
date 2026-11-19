<?php

namespace App\Modules\Notifications\Listeners;

use App\Modules\Notifications\Messages\VerificationMessage;
use App\Modules\Verification\Events\VerificationApproved;
use App\Modules\Verification\Events\VerificationExpired;
use App\Modules\Verification\Events\VerificationExpiringSoon;
use App\Modules\Verification\Events\VerificationRejected;

/**
 * HU-10.01: verification approved, rejected, about to expire or expired → applicant.
 */
class SendVerificationNotifications
{
    public function approved(VerificationApproved $event): void
    {
        $until = $event->profile->verified_until?->toDateString();

        $event->profile->user->notify(new VerificationMessage(
            'verification.approved',
            'Your verification was approved',
            $until ? 'You are verified until :date.' : 'Your account is verified.',
            $until ? ['date' => $until] : [],
        ));
    }

    public function rejected(VerificationRejected $event): void
    {
        $event->profile->user->notify(new VerificationMessage(
            'verification.rejected',
            'Your verification was not approved',
            'Reason: :reason. You can upload your documents again.',
            ['reason' => (string) $event->profile->rejection_reason],
        ));
    }

    public function expiringSoon(VerificationExpiringSoon $event): void
    {
        $event->profile->user->notify(new VerificationMessage(
            'verification.expiring',
            'Your verification is about to expire',
            'Your verification expires on :date. Renew it to keep requesting rentals.',
            ['date' => (string) $event->profile->verified_until?->toDateString()],
        ));
    }

    public function expired(VerificationExpired $event): void
    {
        $event->profile->user->notify(new VerificationMessage(
            'verification.expired',
            'Your verification has expired',
            'Upload your current documents to request new rentals. Your current rentals are not affected.',
        ));
    }
}
