<?php

namespace App\Modules\Verification;

use App\Modules\Shared\Providers\ModuleServiceProvider;
use App\Modules\Verification\Actions\ExpireVerifications;
use App\Modules\Verification\Actions\SendExpiryReminders;
use App\Modules\Verification\Listeners\SyncProfilesWithVerifiedEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Scheduling\Schedule;

final class VerificationServiceProvider extends ModuleServiceProvider
{
    protected array $listen = [
        Verified::class => [SyncProfilesWithVerifiedEmail::class],
    ];

    /**
     * HU-02.05 · RN-04.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(fn () => app(SendExpiryReminders::class)->handle())
            ->name('verification:send-expiry-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        $schedule->call(fn () => app(ExpireVerifications::class)->handle())
            ->name('verification:expire')
            ->dailyAt('00:10')
            ->withoutOverlapping();
    }
}
