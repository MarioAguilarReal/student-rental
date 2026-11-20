<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Models\Rental;

/**
 * RN-62 / RN-66: when a rental expires or is cancelled, what was not paid is cancelled.
 * Paid charges stay as they are: there are no automatic refunds in the MVP.
 */
class CancelUnpaidCharges
{
    public function handle(Rental $rental): int
    {
        $cancelled = 0;

        $rental->charges()->whereIn('status', ChargeStatus::unpaid())->get()->each(function (Charge $charge) use (&$cancelled) {
            $charge->status->assertCanTransitionTo(ChargeStatus::Cancelled);
            $charge->forceFill(['status' => ChargeStatus::Cancelled])->save();
            $cancelled++;
        });

        return $cancelled;
    }
}
