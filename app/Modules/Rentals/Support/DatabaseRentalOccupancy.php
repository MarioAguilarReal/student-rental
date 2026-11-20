<?php

namespace App\Modules\Rentals\Support;

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Contracts\RentalOccupancy;
use App\Modules\Rentals\Models\Rental;
use Carbon\CarbonImmutable;

final class DatabaseRentalOccupancy implements RentalOccupancy
{
    public function isOccupied(Unit $unit, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return Rental::query()->blocking()->whereIn('unit_id', $unit->familyIds())->overlapping($start, $end)->exists();
    }

    public function hasRentals(Unit $unit): bool
    {
        return Rental::query()->whereIn('unit_id', collect([$unit->id])->merge($unit->descendants()->pluck('id')))->exists();
    }

    public function hasBlockingRentals(Property $property): bool
    {
        return Rental::query()->blocking()->where('property_id', $property->id)->exists();
    }
}
