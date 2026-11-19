<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * HU-04.04: shared-area amenities, house rules (RN-33) and coexistence rules (RN-30, RN-31).
 * None of them is a critical change.
 */
class UpdatePropertyFeatures
{
    public function __construct(private ValidateCoexistenceRules $validateCoexistenceRules) {}

    /**
     * @param  list<int>  $amenityIds
     * @param  array<int, array{is_allowed: bool, note: ?string}>  $houseRules  keyed by house rule id
     * @param  list<string>  $allowedCommunities  empty = everyone
     */
    public function handle(Property $property, array $amenityIds, array $houseRules, GenderPolicy $genderPolicy, array $allowedCommunities): Property
    {
        EnsurePropertyIsEditable::check($property);

        $this->ensureAmenitiesFit($property, $amenityIds);
        $this->ensureHouseRulesExist($property, array_keys($houseRules));

        DB::transaction(function () use ($property, $amenityIds, $houseRules, $genderPolicy, $allowedCommunities) {
            $property->amenities()->sync($amenityIds);
            $property->houseRules()->sync($houseRules);
            $property->update([
                'gender_policy' => $genderPolicy,
                'allowed_communities' => array_values(array_unique($allowedCommunities)),
            ]);

            $this->validateCoexistenceRules->handle($property);
        });

        return $property;
    }

    /**
     * Active amenities for shared areas; inactive ones may stay if already linked.
     *
     * @param  list<int>  $amenityIds
     */
    private function ensureAmenitiesFit(Property $property, array $amenityIds): void
    {
        $linked = $property->amenities()->pluck('amenities.id')->all();

        $valid = Amenity::query()
            ->whereIn('id', $amenityIds)
            ->availableFor(AmenityScope::forProperty())
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $linked))
            ->count();

        if ($valid !== count(array_unique($amenityIds))) {
            throw new BusinessRuleViolation('HU-04.04', __('Some amenities are not available for shared areas.'), 'amenity_ids');
        }
    }

    /**
     * @param  list<int>  $ids
     */
    private function ensureHouseRulesExist(Property $property, array $ids): void
    {
        $linked = $property->houseRules()->pluck('house_rules.id')->all();

        $valid = HouseRule::query()
            ->whereIn('id', $ids)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $linked))
            ->count();

        if ($valid !== count($ids)) {
            throw new BusinessRuleViolation('RN-33', __('Some house rules are not available.'), 'house_rules');
        }
    }
}
