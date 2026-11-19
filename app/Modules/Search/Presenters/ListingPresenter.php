<?php

namespace App\Modules\Search\Presenters;

use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Search\Support\CatalogQuery;
use App\Modules\Search\Support\Geo;
use App\Modules\Verification\Enums\CommunityType;
use Carbon\CarbonImmutable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Public shapes of a property: never the exact address, never the owner's contact (RN-12 note in 04).
 */
class ListingPresenter
{
    public function __construct(private UnitAvailability $availability) {}

    /**
     * @param  array{property: Property, min_price_minor: int, spaces: int, distance_km: float|null}  $result
     * @return array<string, mixed>
     */
    public function card(array $result): array
    {
        $property = $result['property'];
        $cover = $property->getFirstMedia(Property::PHOTOS);

        return [
            'id' => $property->id,
            'title' => $property->title,
            'location' => $property->approximateLocation(),
            'cover' => $cover ? $this->url($cover, 'thumb') : null,
            'min_price_minor' => $result['min_price_minor'],
            'spaces' => $result['spaces'],
            'distance_km' => $result['distance_km'] !== null ? round($result['distance_km'], 1) : null,
            'lat' => Geo::approximate((float) $property->latitude),
            'lng' => Geo::approximate((float) $property->longitude),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Property $property): array
    {
        $property->loadMissing(['owner', 'amenities', 'houseRules']);
        $units = CatalogQuery::rentableUnits($property);
        $plans = RatePlan::query()->whereIn('unit_id', $units->modelKeys())->where('is_active', true)->get()->groupBy('unit_id');
        $deposits = UnitDeposit::query()->whereIn('unit_id', $units->modelKeys())->pluck('amount_minor', 'unit_id');
        $today = CarbonImmutable::today();

        return [
            'property' => [
                'id' => $property->id,
                'title' => $property->title,
                'description' => $property->description,
                'property_type' => $property->property_type->label(),
                'location' => $property->approximateLocation(),
                'lat' => $property->latitude !== null ? Geo::approximate((float) $property->latitude) : null,
                'lng' => $property->longitude !== null ? Geo::approximate((float) $property->longitude) : null,
                'inspected_until' => $property->inspection_valid_until?->toDateString(),
                'host' => $property->management_mode === ManagementMode::Managed ? null : $property->owner->first_name,
                'managed' => $property->management_mode === ManagementMode::Managed,
                'gender_policy' => $property->gender_policy->label(),
                'communities' => $this->communities($property->allowed_communities),
                'listed' => CatalogQuery::isListed($property),
            ],
            'photos' => $property->getMedia(Property::PHOTOS)->map(fn (Media $photo): array => [
                'id' => $photo->id,
                'thumb' => $this->url($photo, 'thumb'),
                'url' => $this->url($photo, 'large'),
                'alt' => $photo->getCustomProperty('alt'),
            ])->values(),
            'amenities' => $property->amenities->map(fn (Amenity $amenity): array => ['name' => $amenity->name, 'category' => $amenity->category->label()])->values(),
            'houseRules' => $property->houseRules->map(fn (HouseRule $rule): array => [
                'name' => $rule->name,
                'allowed' => (bool) $rule->getRelationValue('pivot')?->getAttribute('is_allowed'),
                'note' => $rule->getRelationValue('pivot')?->getAttribute('note'),
            ])->values(),
            'units' => $units->filter(fn (Unit $unit) => $plans->has($unit->id))->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'name' => $unit->name,
                'type' => $unit->type->label(),
                'parent' => $unit->parent?->name,
                'capacity' => $unit->capacity,
                'description' => $unit->description,
                'gender_policy' => $unit->effectiveGenderPolicy()->label(),
                'communities' => $this->communities($unit->effectiveAllowedCommunities()),
                'amenities' => $unit->amenities->pluck('name')->values(),
                'deposit_minor' => (int) ($deposits[$unit->id] ?? 0),
                'plans' => $plans->get($unit->id)?->sortBy(fn (RatePlan $plan) => array_search($plan->period->value, ['weekly', 'monthly', 'yearly'], true))->map(fn (RatePlan $plan): array => [
                    'period' => $plan->period->value,
                    'label' => $plan->period->label(),
                    'price_minor' => $plan->price_minor,
                    'min_periods' => $plan->min_periods,
                    'max_periods' => $plan->max_periods,
                ])->values(),
                'unavailable' => $this->availability->unavailableRanges($unit, $today, $today->addMonthsNoOverflow(12)),
            ])->values(),
        ];
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function communities(array $values): array
    {
        return array_map(fn (string $value): string => CommunityType::from($value)->label(), $values);
    }

    private function url(Media $photo, string $conversion): string
    {
        return $photo->hasGeneratedConversion($conversion) ? $photo->getUrl($conversion) : $photo->getUrl();
    }
}
