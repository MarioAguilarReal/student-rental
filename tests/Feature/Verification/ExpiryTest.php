<?php

use App\Modules\Notifications\Messages\VerificationMessage;
use App\Modules\Verification\Actions\ExpireVerifications;
use App\Modules\Verification\Actions\SendExpiryReminders;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Support\Facades\Notification;

/**
 * HU-02.05 · Vencimiento y renovación (RN-04).
 */
beforeEach(fn () => Notification::fake());

it('reminds once, 15 days before the verification expires', function () {
    $soon = VerificationProfile::factory()->verified('+15 days')->create();
    $later = VerificationProfile::factory()->verified('+16 days')->create();

    expect(app(SendExpiryReminders::class)->handle())->toBe(1)
        ->and(app(SendExpiryReminders::class)->handle())->toBe(0);

    Notification::assertSentTo($soon->user, VerificationMessage::class, fn (VerificationMessage $message) => $message->type === 'verification.expiring');
    Notification::assertNotSentTo($later->user, VerificationMessage::class);
});

it('expires verifications after their validity date', function () {
    $expired = VerificationProfile::factory()->verified('-1 day')->create();
    $lastDay = VerificationProfile::factory()->verified('today')->create();
    $owner = VerificationProfile::factory()->owner()->verified(null)->create();

    expect(app(ExpireVerifications::class)->handle())->toBe(1);

    expect($expired->fresh()?->status)->toBe(VerificationStatus::Expired)
        ->and($lastDay->fresh()?->status)->toBe(VerificationStatus::Verified)
        ->and($owner->fresh()?->status)->toBe(VerificationStatus::Verified);
    Notification::assertSentTo($expired->user, VerificationMessage::class, fn (VerificationMessage $message) => $message->type === 'verification.expired');
});

it('treats a verification past its date as not verified even before the daily job runs', function () {
    $profile = VerificationProfile::factory()->verified('-1 day')->create();

    expect($profile->isVerified())->toBeFalse();
});

it('accepts new documents once expired', function () {
    expect(VerificationStatus::Expired->acceptsSubmissions())->toBeTrue()
        ->and(VerificationStatus::Verified->acceptsSubmissions())->toBeFalse();
});

it('schedules both daily jobs', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('verification:send-expiry-reminders')
        ->expectsOutputToContain('verification:expire');
});
