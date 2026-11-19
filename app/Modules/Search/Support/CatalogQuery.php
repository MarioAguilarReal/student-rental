<?php

namespace App\Modules\Search\Support;

use App\Models\User;
use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Support\RentalPeriods;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Verification\Enums\VerificationKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * HU-07.01: what can be rented right now, grouped by property.
 *
 * Only rentable, active spaces with an active plan for the period, in published properties
 * with a current inspection. SQL narrows by what is indexable (status, type, price, a box around
 * the campus); PHP applies what depends on the unit tree (coexistence, availability) and the
 * exact distance. Fine for the MVP's volume; revisit with PostGIS/materialized views (D-64).
 */
class CatalogQuery
{
    public function __construct(private UnitAvailability $availability) {}

    /**
     * @return Collection<int, array{property: Property, min_price_minor: int, spaces: int, distance_km: float|null}>
     */
    public function search(CatalogFilters $filters, ?User $viewer = null): Collection
    {
        $profile = $viewer?->verificationProfile(VerificationKind::Client);
        $community = $profile?->isVerified() ? $profile->community_type?->value : null;

        $plans = RatePlan::query()
            ->where('rate_plans.is_active', true)
            ->where('rate_plans.period', $filters->period)
            ->when($filters->minPriceMinor !== null, fn ($query) => $query->where('price_minor', '>=', $filters->minPriceMinor))
            ->when($filters->maxPriceMinor !== null, fn ($query) => $query->where('price_minor', '<=', $filters->maxPriceMinor))
            ->whereHas('unit', function ($unit) use ($filters) {
                $unit->where('is_rentable', true)
                    ->where('status', UnitStatus::Active)
                    ->when($filters->unitType, fn ($query, $type) => $query->where('type', $type))
                    ->whereHas('property', function ($property) use ($filters) {
                        $property->where('status', PropertyStatus::Published)
                            ->whereDate('inspection_valid_until', '>=', CarbonImmutable::today());

                        if ($filters->campus) {
                            $box = Geo::boundingBox((float) $filters->campus->latitude, (float) $filters->campus->longitude, $filters->radiusKm);
                            $property->whereBetween('latitude', [$box['min_lat'], $box['max_lat']])
                                ->whereBetween('longitude', [$box['min_lng'], $box['max_lng']]);
                        }
                    });

                foreach ($filters->amenityIds as $amenityId) {
                    $unit->where(fn ($query) => $query
                        ->whereHas('amenities', fn ($amenity) => $amenity->whereKey($amenityId))
                        ->orWhereHas('property.amenities', fn ($amenity) => $amenity->whereKey($amenityId)));
                }
            })
            ->with(['unit.property.owner', 'unit.parent.parent'])
            ->get();

        $results = [];

        foreach ($plans as $plan) {
            $unit = $plan->unit;
            $property = $unit->property;

            if ($filters->genderPolicy && $unit->effectiveGenderPolicy() !== $filters->genderPolicy) {
                continue;
            }

            $communities = $unit->effectiveAllowedCommunities();
            if ($community && $communities !== [] && ! in_array($community, $communities, true)) {
                continue;
            }

            if ($filters->moveIn && ! $this->availability->isAvailable($unit, $filters->moveIn, RentalPeriods::endDate($filters->moveIn, $filters->period, 1))) {
                continue;
            }

            $distance = $filters->campus
                ? Geo::distanceKm((float) $filters->campus->latitude, (float) $filters->campus->longitude, (float) $property->latitude, (float) $property->longitude)
                : null;

            if ($distance !== null && $distance > $filters->radiusKm) {
                continue;
            }

            $results[$property->id] ??= ['property' => $property, 'min_price_minor' => $plan->price_minor, 'spaces' => 0, 'distance_km' => $distance];
            $results[$property->id]['min_price_minor'] = min($results[$property->id]['min_price_minor'], $plan->price_minor);
            $results[$property->id]['spaces']++;
        }

        $collection = collect(array_values($results));

        return match ($filters->sort) {
            'price' => $collection->sortBy('min_price_minor')->values(),
            'distance' => $collection->sortBy(fn (array $result) => $result['distance_km'] ?? PHP_FLOAT_MAX)->values(),
            default => $collection->sortByDesc(fn (array $result) => $result['property']->published_at?->getTimestamp() ?? 0)->values(),
        };
    }

    /**
     * RN-10 visibility: published with a current inspection.
     */
    public static function isListed(Property $property): bool
    {
        return $property->status === PropertyStatus::Published && $property->hasValidInspection();
    }

    /**
     * Spaces of a property a visitor can see and quote.
     *
     * @return EloquentCollection<int, Unit>
     */
    public static function rentableUnits(Property $property): EloquentCollection
    {
        return $property->units()->where('is_rentable', true)->where('status', UnitStatus::Active)->with('amenities', 'parent.parent', 'property')->get();
    }
}
