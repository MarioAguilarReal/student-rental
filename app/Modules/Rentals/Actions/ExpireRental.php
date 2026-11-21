<?php

namespace App\Modules\Rentals\Actions;

use App\Modules\Payments\Actions\CancelUnpaidCharges;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalExpired;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\DB;

/**
 * HU-08.03: a request without answer in 72 h (RN-61) or an approval without payment in
 * 48 h (RN-62) expires and frees the dates; unpaid charges are cancelled.
 * An approval whose first payment is still being processed waits for the gateway's answer
 * (D-75). Idempotent: a rental that is no longer due to expire is left alone.
 */
class ExpireRental
{
    public function __construct(private CancelUnpaidCharges $cancelCharges) {}

    public function handle(Rental $rental): bool
    {
        return DB::transaction(function () use ($rental) {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);

            if (! in_array($rental->status, [RentalStatus::Requested, RentalStatus::Approved], true)
                || $rental->expires_at === null
                || $rental->expires_at->isFuture()) {
                return false;
            }

            if ($rental->status === RentalStatus::Approved && $this->paymentInFlight($rental)) {
                return false;
            }

            $rental->status->assertCanTransitionTo(RentalStatus::Expired);

            if ($rental->status === RentalStatus::Approved) {
                $this->cancelCharges->handle($rental);
            }

            $rental->forceFill(['status' => RentalStatus::Expired])->save();

            RentalExpired::dispatch($rental);

            return true;
        });
    }

    private function paymentInFlight(Rental $rental): bool
    {
        return $rental->charges()->whereHas('attempts', fn ($attempt) => $attempt->where('status', PaymentAttemptStatus::Processing))->exists();
    }
}
