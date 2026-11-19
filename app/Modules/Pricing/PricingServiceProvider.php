<?php

namespace App\Modules\Pricing;

use App\Modules\Pricing\Contracts\RatePlanCatalog;
use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Support\BlocksAvailability;
use App\Modules\Pricing\Support\DatabaseRatePlanCatalog;
use App\Modules\Shared\Providers\ModuleServiceProvider;

final class PricingServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RatePlanCatalog::class, DatabaseRatePlanCatalog::class);
        $this->app->bind(UnitAvailability::class, BlocksAvailability::class);
    }
}
