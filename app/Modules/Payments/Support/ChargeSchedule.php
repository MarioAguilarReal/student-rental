<?php

namespace App\Modules\Payments\Support;

use App\Modules\Payments\Enums\ChargeConcept;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Shared\Money;
use Carbon\CarbonImmutable;

/**
 * RN-70: the charges a rental will generate when approved.
 *
 * - Weekly: a single charge = whole rent + deposit.
 * - Monthly: charge 1 = first month + deposit; then one per month, due when each month starts.
 * - Yearly: each year's price in 12 monthly installments (the last absorbs the rounding);
 *   charge 1 = installment 1 + deposit.
 *
 * Charge 1 is due when the rental is approved (the client has 48 h to pay it, RN-62); the
 * others on the first day of their period, counted from the start date as in RN-42.
 */
final class ChargeSchedule
{
    /**
     * @return list<array{number: int, concept: ChargeConcept, rent: Money, deposit: Money, due_on: CarbonImmutable}>
     */
    public static function plan(RatePeriod $period, int $periods, CarbonImmutable $startsOn, Money $price, Money $deposit, CarbonImmutable $firstDueOn): array
    {
        $rents = match ($period) {
            RatePeriod::Weekly => [$price->multipliedBy($periods)],
            RatePeriod::Monthly => array_fill(0, $periods, $price),
            RatePeriod::Yearly => array_merge(...array_fill(0, $periods, $price->split(12))),
        };

        $charges = [];

        foreach ($rents as $index => $rent) {
            $first = $index === 0;

            $charges[] = [
                'number' => $index + 1,
                'concept' => $first && ! $deposit->isZero() ? ChargeConcept::RentAndDeposit : ChargeConcept::Rent,
                'rent' => $rent,
                'deposit' => $first ? $deposit : Money::zero($deposit->currency()),
                'due_on' => $first ? $firstDueOn : $startsOn->addMonthsNoOverflow($index),
            ];
        }

        return $charges;
    }
}
