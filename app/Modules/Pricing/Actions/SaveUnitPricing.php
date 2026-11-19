<?php

namespace App\Modules\Pricing\Actions;

use App\Models\User;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Events\UnitPricingUpdated;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Actions\EnsurePropertyIsEditable;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Money;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-05.01 / HU-05.02: the unit's plans and deposit in one save.
 * RN-40: one plan per period (disabled plans keep their row). RN-43: min/max periods.
 * RN-44: deposit ≥ 0. Prices are not a critical change (RN-11). Rentals keep their own copy (RN-45).
 */
class SaveUnitPricing
{
    /**
     * @param  array<string, array{enabled: bool, price_minor: int, min_periods: int, max_periods: int}>  $plans  keyed by period
     */
    public function handle(User $actor, Unit $unit, array $plans, int $depositMinor): void
    {
        EnsurePropertyIsEditable::check($unit->property);

        if (! $unit->is_rentable) {
            throw new BusinessRuleViolation('RN-21', Text::of('This space is not rented on its own, so it has no prices.'));
        }

        $this->validate($plans, $depositMinor);
        $before = $this->snapshot($unit);

        DB::transaction(function () use ($unit, $plans, $depositMinor) {
            foreach (RatePeriod::cases() as $period) {
                $plan = $plans[$period->value] ?? null;
                $existing = RatePlan::query()->where('unit_id', $unit->id)->where('period', $period)->first();

                if (! $plan || ! $plan['enabled']) {
                    $existing?->update(['is_active' => false]);

                    continue;
                }

                RatePlan::query()->updateOrCreate(
                    ['unit_id' => $unit->id, 'period' => $period],
                    [
                        'price_minor' => $plan['price_minor'],
                        'currency' => Money::DEFAULT_CURRENCY,
                        'min_periods' => $plan['min_periods'],
                        'max_periods' => $plan['max_periods'],
                        'is_active' => true,
                    ],
                );
            }

            UnitDeposit::query()->updateOrCreate(['unit_id' => $unit->id], ['amount_minor' => $depositMinor, 'currency' => Money::DEFAULT_CURRENCY]);
        });

        $after = $this->snapshot($unit);

        if ($before !== $after) {
            UnitPricingUpdated::dispatch($unit, $actor, $before, $after);
        }
    }

    /**
     * @param  array<string, array{enabled: bool, price_minor: int, min_periods: int, max_periods: int}>  $plans
     */
    private function validate(array $plans, int $depositMinor): void
    {
        foreach ($plans as $periodValue => $plan) {
            $period = RatePeriod::from($periodValue);

            if (! $plan['enabled']) {
                continue;
            }

            if ($plan['price_minor'] <= 0) {
                throw new BusinessRuleViolation('RN-40', Text::of('Enter a price for the :period plan.', ['period' => $period->label()]), "plans.{$period->value}.price");
            }

            if ($plan['min_periods'] < 1 || $plan['max_periods'] < $plan['min_periods'] || $plan['max_periods'] > $period->maxPeriodsLimit()) {
                throw new BusinessRuleViolation('RN-43', Text::of('In the :period plan the minimum must be at least 1 and the maximum between the minimum and :limit.', [
                    'period' => $period->label(),
                    'limit' => $period->maxPeriodsLimit(),
                ]), "plans.{$period->value}.max_periods");
            }
        }

        if ($depositMinor < 0) {
            throw new BusinessRuleViolation('RN-44', Text::of('The deposit cannot be negative.'), 'deposit');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Unit $unit): array
    {
        return [
            'plans' => RatePlan::query()->where('unit_id', $unit->id)->orderBy('period')
                ->get(['period', 'price_minor', 'min_periods', 'max_periods', 'is_active'])
                ->map(fn (RatePlan $plan): array => [
                    'period' => $plan->period->value,
                    'price_minor' => $plan->price_minor,
                    'min' => $plan->min_periods,
                    'max' => $plan->max_periods,
                    'active' => $plan->is_active,
                ])->all(),
            'deposit_minor' => UnitDeposit::query()->where('unit_id', $unit->id)->value('amount_minor'),
        ];
    }
}
