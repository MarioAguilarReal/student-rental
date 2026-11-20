<?php

namespace App\Modules\Rentals\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\CancelUnpaidCharges;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalCancelled;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-08.07 · RN-66: only an administrator cancels, always with a reason. Unpaid charges are
 * cancelled; there is no automatic refund in the MVP (B-01). The dates are freed.
 */
class CancelRental
{
    public function __construct(private CancelUnpaidCharges $cancelCharges) {}

    public function handle(User $admin, Rental $rental, string $reason): Rental
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BusinessRuleViolation('RN-66', Text::of('Write the reason for the cancellation.'), 'reason');
        }

        return DB::transaction(function () use ($admin, $rental, $reason) {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
            $rental->status->assertCanTransitionTo(RentalStatus::Cancelled);

            $this->cancelCharges->handle($rental);

            $rental->forceFill([
                'status' => RentalStatus::Cancelled,
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'expires_at' => null,
            ])->save();

            RentalCancelled::dispatch($rental, $admin);

            return $rental;
        });
    }
}
