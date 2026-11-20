<?php

namespace App\Modules\Rentals;

use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Support\BlocksAvailability;
use App\Modules\Rentals\Actions\AdvanceRentals;
use App\Modules\Rentals\Actions\ExpireRentals;
use App\Modules\Rentals\Contracts\RentalOccupancy;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Rentals\Policies\RentalPolicy;
use App\Modules\Rentals\Support\DatabaseRentalOccupancy;
use App\Modules\Rentals\Support\RentalAwareAvailability;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

final class RentalsServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Rental::class => RentalPolicy::class,
    ];

    public function register(): void
    {
        $this->app->bind(RentalOccupancy::class, DatabaseRentalOccupancy::class);

        // D-66: availability now counts blocking rentals as well as Pricing's blocks.
        $this->app->bind(UnitAvailability::class, fn ($app) => new RentalAwareAvailability($app->make(BlocksAvailability::class)));
    }

    /**
     * HU-08.03 (RN-61, RN-62) and HU-08.06 (RN-65).
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(fn () => app(ExpireRentals::class)->handle())
            ->name('rentals:expire')->everyFifteenMinutes()->withoutOverlapping();

        $schedule->call(fn () => app(AdvanceRentals::class)->handle())
            ->name('rentals:advance')->dailyAt('00:05')->withoutOverlapping();
    }
}
