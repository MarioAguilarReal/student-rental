<?php

use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Support\RentalPeriods;
use Carbon\CarbonImmutable;

/**
 * RN-42: end dates (exclusive).
 */
it('computes end dates by period', function (string $start, RatePeriod $period, int $periods, string $end) {
    expect(RentalPeriods::endDate(CarbonImmutable::parse($start), $period, $periods)->toDateString())->toBe($end);
})->with([
    'two weeks' => ['2027-01-04', RatePeriod::Weekly, 2, '2027-01-18'],
    'one month' => ['2027-02-15', RatePeriod::Monthly, 1, '2027-03-15'],
    'month from the 31st keeps to the last day' => ['2027-01-31', RatePeriod::Monthly, 1, '2027-02-28'],
    'semester' => ['2027-08-01', RatePeriod::Monthly, 5, '2028-01-01'],
    'one year' => ['2027-08-01', RatePeriod::Yearly, 1, '2028-08-01'],
]);
