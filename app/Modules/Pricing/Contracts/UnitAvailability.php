<?php

namespace App\Modules\Pricing\Contracts;

use App\Modules\Properties\Models\Unit;
use Carbon\CarbonImmutable;

/**
 * RN-22: whether a unit can be rented in [start, end), looking at the unit, its ancestors and
 * its descendants. Pricing counts its blocks; Rentals decorates it with blocking rentals (D-66).
 */
interface UnitAvailability
{
    public function isAvailable(Unit $unit, CarbonImmutable $start, CarbonImmutable $end): bool;

    /**
     * Unavailable ranges of the unit's family from $from on, merged and sorted.
     *
     * @return list<array{starts_on: string, ends_on: string}>
     */
    public function unavailableRanges(Unit $unit, CarbonImmutable $from, CarbonImmutable $until): array;
}
