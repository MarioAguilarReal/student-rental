<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Actions\MarkOverdueCharges;
use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Policies\ChargePolicy;
use App\Modules\Payments\Support\DatabaseCommissionRates;
use App\Modules\Payments\Support\SimulatedGateway;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

final class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Charge::class => ChargePolicy::class,
    ];

    public function register(): void
    {
        $this->app->bind(CommissionRates::class, DatabaseCommissionRates::class);

        // D-05: the gateway is chosen here; a real one is a new implementation.
        $this->app->bind(PaymentGateway::class, SimulatedGateway::class);
    }

    /**
     * RN-74.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(fn () => app(MarkOverdueCharges::class)->handle())
            ->name('payments:overdue')->dailyAt('00:10')->withoutOverlapping();
    }
}
