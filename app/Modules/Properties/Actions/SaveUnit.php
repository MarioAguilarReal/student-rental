<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * HU-04.02 / HU-04.04: adds or edits a unit.
 * RN-20: hierarchy. RN-30/RN-31: coexistence. RN-11: adding a unit, or changing its type or
 * capacity, is critical and sends an inspected property back to inspection.
 */
class SaveUnit
{
    public function __construct(
        private ValidateCoexistenceRules $validateCoexistenceRules,
        private MarkForReinspection $markForReinspection,
    ) {}

    /**
     * @param  list<string>|null  $allowedCommunities  null = inherit
     * @param  list<int>  $amenityIds
     */
    public function handle(
        Property $property,
        ?Unit $unit,
        ?Unit $parent,
        UnitType $type,
        string $name,
        ?string $description,
        int $capacity,
        bool $isRentable,
        ?GenderPolicy $genderPolicy,
        ?array $allowedCommunities,
        array $amenityIds = [],
    ): Unit {
        EnsurePropertyIsEditable::check($property);
        $this->ensureHierarchy($property, $unit, $parent, $type);
        $this->ensureAmenitiesFit($amenityIds);

        if ($capacity < 1) {
            throw new BusinessRuleViolation('RN-20', __('Capacity must be at least one person.'), 'capacity');
        }

        return DB::transaction(function () use ($property, $unit, $parent, $type, $name, $description, $capacity, $isRentable, $genderPolicy, $allowedCommunities, $amenityIds) {
            $unit ??= new Unit(['property_id' => $property->id, 'position' => $property->units()->count()]);
            $isNew = ! $unit->exists;

            $unit->fill([
                'parent_id' => $parent?->id,
                'type' => $type,
                'name' => $name,
                'description' => $description,
                'capacity' => $capacity,
                'is_rentable' => $isRentable,
                'gender_policy' => $genderPolicy,
                'allowed_communities' => $allowedCommunities === null ? null : array_values(array_unique($allowedCommunities)),
            ]);

            $isCritical = $isNew || $unit->isDirty(['type', 'capacity', 'parent_id']);
            $unit->save();
            $unit->amenities()->sync($amenityIds);

            $this->validateCoexistenceRules->handle($property);

            if ($isCritical) {
                $this->markForReinspection->handle($property);
            }

            return $unit;
        });
    }

    private function ensureHierarchy(Property $property, ?Unit $unit, ?Unit $parent, UnitType $type): void
    {
        if ($parent === null && ! $type->canBeRoot()) {
            throw new BusinessRuleViolation('RN-20', __('A bed must belong to a shared room.'), 'parent_id');
        }

        if ($parent !== null) {
            if ($parent->property_id !== $property->id || ($unit && $parent->id === $unit->id)) {
                throw new BusinessRuleViolation('RN-20', __('Invalid parent space.'), 'parent_id');
            }

            if ($unit && $unit->descendants()->contains('id', $parent->id)) {
                throw new BusinessRuleViolation('RN-20', __('A space cannot be placed inside one of its own subdivisions.'), 'parent_id');
            }

            if (! in_array($type, $parent->type->allowedChildren(), true)) {
                throw new BusinessRuleViolation('RN-20', __(':parent cannot contain a :type.', [
                    'parent' => $parent->type->label(),
                    'type' => $type->label(),
                ]), 'type');
            }
        }

        if ($unit) {
            foreach ($unit->children as $child) {
                if (! in_array($child->type, $type->allowedChildren(), true)) {
                    throw new BusinessRuleViolation('RN-20', __('This type cannot keep its current subdivisions. Remove or move them first.'), 'type');
                }
            }
        }
    }

    /**
     * @param  list<int>  $amenityIds
     */
    private function ensureAmenitiesFit(array $amenityIds): void
    {
        $valid = Amenity::query()->whereIn('id', $amenityIds)->availableFor(AmenityScope::forUnit())->count();

        if ($valid !== count(array_unique($amenityIds))) {
            throw new BusinessRuleViolation('HU-04.04', __('Some amenities are not available for individual spaces.'), 'amenity_ids');
        }
    }
}
