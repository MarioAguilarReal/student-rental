<?php

namespace App\Modules\Rentals\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\GenerateCharges;
use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Properties\Models\Property;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalApproved;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * HU-08.02: the operator approves a request (RN-60).
 *
 * RN-25: in one transaction holding a lock on the property, approvals that ran out of time are
 * expired, availability is checked again for the unit's family (RN-22, RN-24) and for the
 * client's own rentals (RN-64), the charge schedule is generated (RN-70) and the client gets
 * 48 h to pay (RN-62). The database exclusion constraint is the last line of defense.
 */
class ApproveRental
{
    public function __construct(
        private UnitAvailability $availability,
        private ExpireRentals $expireRentals,
        private GenerateCharges $generateCharges,
    ) {}

    public function handle(User $operator, Rental $rental): Rental
    {
        try {
            return DB::transaction(function () use ($operator, $rental) {
                Property::query()->lockForUpdate()->findOrFail($rental->property_id);

                $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
                $rental->status->assertCanTransitionTo(RentalStatus::Approved);

                if ($rental->expires_at?->isPast()) {
                    throw new BusinessRuleViolation('RN-61', Text::of('This request has expired.'));
                }

                $this->expireRentals->handle($rental->property_id);

                if (! $this->availability->isAvailable($rental->unit, $rental->starts_on, $rental->ends_on)) {
                    throw new BusinessRuleViolation('RN-24', Text::of('The space is no longer available on those dates.'));
                }

                $clientOverlap = Rental::query()
                    ->blocking()
                    ->where('client_id', $rental->client_id)
                    ->overlapping($rental->starts_on, $rental->ends_on)
                    ->exists();

                if ($clientOverlap) {
                    throw new BusinessRuleViolation('RN-64', Text::of('The client already has a rental on those dates.'));
                }

                $rental->forceFill([
                    'status' => RentalStatus::Approved,
                    'decided_by' => $operator->id,
                    'decided_at' => now(),
                    'expires_at' => now()->addHours(Rental::PAYMENT_HOURS),
                ])->save();

                $this->generateCharges->handle($rental);

                RentalApproved::dispatch($rental, $operator);

                return $rental;
            });
        } catch (QueryException $exception) {
            // 23P01 = exclusion_violation (rentals_no_overlap): another approval won the race.
            if ($exception->getCode() === '23P01') {
                throw new BusinessRuleViolation('RN-25', Text::of('The space is no longer available on those dates.'));
            }

            throw $exception;
        }
    }
}
