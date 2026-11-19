<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Properties\Enums\ManagementMode;
use Carbon\CarbonImmutable;

/**
 * What other modules need to know about commissions: the rate in force (RN-50).
 */
interface CommissionRates
{
    /**
     * Basis points (1000 = 10 %) for the mode on the given date (today by default).
     */
    public function basisPointsFor(ManagementMode $mode, ?CarbonImmutable $on = null): int;
}
