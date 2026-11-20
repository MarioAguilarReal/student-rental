<?php

namespace App\Modules\Search\Support;

use App\Modules\Payments\Support\ChargeSchedule;
use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Pricing\Support\RentalPeriods;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Money;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;

/**
 * HU-07.02: what a stay would cost before requesting it. Same rules the rental request will
 * enforce: start from tomorrow up to 12 months ahead (RN-63), periods within the plan's range
 * (RN-43), end date by RN-42, availability of the space's family (RN-22), and the charges the
 * approval would generate (RN-70).
 */
class Quote
{
    public function __construct(private UnitAvailability $availability) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Unit $unit, RatePeriod $period, CarbonImmutable $start, int $periods): array
    {
        $plan = RatePlan::query()->where('unit_id', $unit->id)->where('period', $period)->where('is_active', true)->first();
        $deposit = (int) (UnitDeposit::query()->where('unit_id', $unit->id)->value('amount_minor') ?? 0);
        $errors = [];

        if (! $plan) {
            $errors[] = Text::of('This space is not offered with that period.');
        }

        $today = CarbonImmutable::today();
        if ($start->lessThanOrEqualTo($today) || $start->greaterThan($today->addMonthsNoOverflow(12))) {
            $errors[] = Text::of('The move-in date must be from tomorrow and within 12 months.');
        }

        if ($plan && ($periods < $plan->min_periods || $periods > $plan->max_periods)) {
            $errors[] = Text::of('For this space choose between :min and :max periods.', ['min' => $plan->min_periods, 'max' => $plan->max_periods]);
        }

        $end = RentalPeriods::endDate($start, $period, max(1, $periods));
        $available = $errors === [] && $this->availability->isAvailable($unit, $start, $end);

        if ($errors === [] && ! $available) {
            $errors[] = Text::of('The space is not available on those dates.');
        }

        $charges = $plan && $errors === []
            ? array_map(fn (array $charge): array => [
                'number' => $charge['number'],
                'amount_minor' => $charge['rent']->plus($charge['deposit'])->minorAmount(),
                'deposit_minor' => $charge['deposit']->minorAmount(),
                'due_on' => $charge['number'] === 1 ? null : $charge['due_on']->toDateString(),
            ], ChargeSchedule::plan($period, $periods, $start, $plan->price(), Money::ofMinor($deposit, $plan->currency), $today))
            : [];

        return [
            'unit_id' => $unit->id,
            'period' => $period->value,
            'start' => $start->toDateString(),
            'periods' => $periods,
            'end' => $end->toDateString(),
            'price_minor' => $plan?->price_minor,
            'total_minor' => $plan ? $plan->price_minor * $periods : null,
            'deposit_minor' => $deposit,
            'available' => $available,
            'errors' => $errors,
            'charges' => $charges,
        ];
    }
}
