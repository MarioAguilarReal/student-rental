<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Support\ChargeSchedule;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * HU-08.02 · RN-70: creates the charge schedule of an approved rental from its copied
 * commercial values (RN-45). Amounts never change afterwards (RN-75).
 */
class GenerateCharges
{
    public function handle(Rental $rental, ?CarbonImmutable $firstDueOn = null): void
    {
        if ($rental->charges()->exists()) {
            throw new LogicException("Rental {$rental->id} already has charges.");
        }

        $plan = ChargeSchedule::plan(
            $rental->period,
            $rental->periods,
            $rental->starts_on,
            Money::ofMinor($rental->price_minor, $rental->currency),
            Money::ofMinor($rental->deposit_minor, $rental->currency),
            $firstDueOn ?? CarbonImmutable::today(),
        );

        DB::transaction(function () use ($rental, $plan) {
            foreach ($plan as $charge) {
                Charge::create([
                    'rental_id' => $rental->id,
                    'number' => $charge['number'],
                    'concept' => $charge['concept'],
                    'rent_minor' => $charge['rent']->minorAmount(),
                    'deposit_minor' => $charge['deposit']->minorAmount(),
                    'currency' => $rental->currency,
                    'due_on' => $charge['due_on'],
                    'status' => ChargeStatus::Pending,
                ]);
            }
        });
    }
}
