<?php

namespace App\Modules\Pricing\Support;

use App\Modules\Pricing\Enums\RatePeriod;
use Carbon\CarbonImmutable;

/**
 * RN-42: weekly = 7 days; monthly = same day of the next month (or that month's last day);
 * yearly = 12 calendar months. The end date is exclusive: [start, end).
 */
final class RentalPeriods
{
    public static function endDate(CarbonImmutable $start, RatePeriod $period, int $periods): CarbonImmutable
    {
        return match ($period) {
            RatePeriod::Weekly => $start->addDays(7 * $periods),
            RatePeriod::Monthly => $start->addMonthsNoOverflow($periods),
            RatePeriod::Yearly => $start->addMonthsNoOverflow(12 * $periods),
        };
    }
}
