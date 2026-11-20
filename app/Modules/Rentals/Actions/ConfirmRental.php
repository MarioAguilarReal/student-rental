<?php

namespace App\Modules\Rentals\Actions;

use App\Modules\Properties\Models\Property;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalConfirmed;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * RN-71: an approved rental is confirmed when its first charge is paid (called by Payments,
 * Iteración 7). RN-24: requests of the same family and dates are then rejected automatically
 * with the reason "no availability", and their clients are notified.
 */
class ConfirmRental
{
    public function __construct(private RejectRental $reject) {}

    public function handle(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            Property::query()->lockForUpdate()->findOrFail($rental->property_id);

            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
            $rental->status->assertCanTransitionTo(RentalStatus::Confirmed);

            $rental->forceFill([
                'status' => RentalStatus::Confirmed,
                'confirmed_at' => now(),
                'expires_at' => null,
            ])->save();

            Rental::query()
                ->where('status', RentalStatus::Requested)
                ->whereIn('unit_id', $rental->unit->familyIds())
                ->overlapping($rental->starts_on, $rental->ends_on)
                ->get()
                ->each(fn (Rental $competing) => $this->reject->handle(null, $competing, Text::of('No availability: the space was rented for those dates.')));

            RentalConfirmed::dispatch($rental);

            return $rental;
        });
    }
}
