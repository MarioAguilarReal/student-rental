<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Contracts\RentalOccupancy;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-04.02: removing a unit removes its subdivisions too, and is critical (RN-11).
 * D-69: a space that has (or had) rentals is never removed, to keep their history; it can
 * be marked as not rentable instead.
 */
class RemoveUnit
{
    public function __construct(
        private MarkForReinspection $markForReinspection,
        private RentalOccupancy $rentals,
    ) {}

    public function handle(Unit $unit): void
    {
        $property = $unit->property;
        EnsurePropertyIsEditable::check($property);

        if ($this->rentals->hasRentals($unit)) {
            throw new BusinessRuleViolation('D-69', Text::of('This space has rentals, so it cannot be removed. Mark it as not rentable instead.'));
        }

        DB::transaction(function () use ($unit, $property) {
            $unit->delete();
            $this->markForReinspection->handle($property);
        });
    }
}
