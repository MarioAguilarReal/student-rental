<?php

namespace App\Modules\Rentals\Actions;

use App\Models\User;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalRejected;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-08.02 · RN-67: the operator rejects a request with a reason the client will read.
 * Also used for automatic rejections (RN-24), where there is no operator.
 */
class RejectRental
{
    public function handle(?User $operator, Rental $rental, string $reason): Rental
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BusinessRuleViolation('RN-67', Text::of('Tell the client why the request is rejected.'), 'reason');
        }

        return DB::transaction(function () use ($operator, $rental, $reason) {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
            $rental->status->assertCanTransitionTo(RentalStatus::Rejected);

            $rental->forceFill([
                'status' => RentalStatus::Rejected,
                'decided_by' => $operator?->id,
                'decided_at' => now(),
                'rejection_reason' => $reason,
                'expires_at' => null,
            ])->save();

            RentalRejected::dispatch($rental, $operator);

            return $rental;
        });
    }
}
