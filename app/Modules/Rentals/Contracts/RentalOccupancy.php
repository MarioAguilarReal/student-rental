<?php

namespace App\Modules\Rentals\Contracts;

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Carbon\CarbonImmutable;

/**
 * What other modules need to know about rentals before changing units, dates or properties.
 */
interface RentalOccupancy
{
    /**
     * RN-22 / RN-26: a blocking rental of the unit's family overlaps [start, end).
     */
    public function isOccupied(Unit $unit, CarbonImmutable $start, CarbonImmutable $end): bool;

    /**
     * D-69: the unit or one of its subdivisions has ever been requested (its history is kept).
     */
    public function hasRentals(Unit $unit): bool;

    /**
     * RN-13: the property has approved, confirmed or active rentals.
     */
    public function hasBlockingRentals(Property $property): bool;
}
