<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Payments\Support\DatabaseCommissionRates;
use App\Modules\Shared\Providers\ModuleServiceProvider;

final class PaymentsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommissionRates::class, DatabaseCommissionRates::class);
    }
}
