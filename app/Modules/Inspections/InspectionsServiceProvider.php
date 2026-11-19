<?php

namespace App\Modules\Inspections;

use App\Modules\Inspections\Actions\OpenRenewalInspections;
use App\Modules\Inspections\Actions\PauseExpiredProperties;
use App\Modules\Inspections\Listeners\OpenInspectionWhenRequested;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Policies\InspectionPolicy;
use App\Modules\Properties\Events\PropertySubmittedForInspection;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

final class InspectionsServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Inspection::class => InspectionPolicy::class,
    ];

    protected array $listen = [
        PropertySubmittedForInspection::class => [OpenInspectionWhenRequested::class],
    ];

    /**
     * HU-06.06 · RN-85.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(fn () => app(OpenRenewalInspections::class)->handle())
            ->name('inspections:open-renewals')->dailyAt('07:00')->withoutOverlapping();

        $schedule->call(fn () => app(PauseExpiredProperties::class)->handle())
            ->name('inspections:pause-expired')->dailyAt('00:20')->withoutOverlapping();
    }
}
