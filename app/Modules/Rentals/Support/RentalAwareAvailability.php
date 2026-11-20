<?php

namespace App\Modules\Rentals\Support;

use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Models\Rental;
use Carbon\CarbonImmutable;

/**
 * D-66 · RN-22: availability = no availability block (Pricing) and no blocking rental in the
 * unit's family. Decorates the Pricing implementation, so the catalog did not change.
 */
final class RentalAwareAvailability implements UnitAvailability
{
    public function __construct(private UnitAvailability $blocks) {}

    public function isAvailable(Unit $unit, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->blocks->isAvailable($unit, $start, $end)
            && ! Rental::query()->blocking()->whereIn('unit_id', $unit->familyIds())->overlapping($start, $end)->exists();
    }

    public function unavailableRanges(Unit $unit, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $rentals = Rental::query()
            ->blocking()
            ->whereIn('unit_id', $unit->familyIds())
            ->overlapping($from, $until)
            ->get(['starts_on', 'ends_on'])
            ->map(fn (Rental $rental): array => ['starts_on' => $rental->starts_on->toDateString(), 'ends_on' => $rental->ends_on->toDateString()]);

        $ranges = collect($this->blocks->unavailableRanges($unit, $from, $until))
            ->merge($rentals)
            ->sortBy('starts_on')
            ->values();

        $merged = [];

        foreach ($ranges as $range) {
            $last = array_key_last($merged);

            if ($last !== null && $range['starts_on'] <= $merged[$last]['ends_on']) {
                $merged[$last]['ends_on'] = max($merged[$last]['ends_on'], $range['ends_on']);
            } else {
                $merged[] = $range;
            }
        }

        return $merged;
    }
}
