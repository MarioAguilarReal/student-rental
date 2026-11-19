<?php

namespace App\Modules\Pricing\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-42: weekly = 7 days, monthly = calendar month, yearly = 12 calendar months.
 */
enum RatePeriod: string
{
    use HasLabel;

    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * Upper bound for max_periods (about two years of any plan).
     */
    public function maxPeriodsLimit(): int
    {
        return match ($this) {
            self::Weekly => 104,
            self::Monthly => 24,
            self::Yearly => 2,
        };
    }
}
