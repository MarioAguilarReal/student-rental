<?php

namespace App\Modules\Properties\Presenters;

use App\Models\User;
use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Properties\Actions\CheckPublicationRequirements;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Enums\UnitTemplate;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Verification\Enums\CommunityType;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shapes properties for Inertia pages (this module and the dashboard). Read-only: never changes state.
 */
class PropertyPresenter
{
    public function __construct(private CheckPublicationRequirements $publicationRequirements) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Property $property): array
    {
        $cover = $property->getFirstMedia(Property::PHOTOS);

        return [
            'id' => $property->id,
            'title' => $property->title,
            'status' => $property->status->value,
            'status_label' => $property->status->label(),
            'management_mode_label' => $property->management_mode->label(),
            'property_type_label' => $property->property_type->label(),
            'location' => $property->approximateLocation(),
            'owner' => $property->owner->name,
            'cover' => $cover ? $this->photoUrl($cover, 'thumb') : null,
            'units_count' => $property->units_count ?? $property->units()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forEditing(Property $property, User $viewer): array
    {
        $property->load(['owner', 'units.amenities', 'amenities', 'houseRules']);
        $proof = $property->getFirstMedia(Property::OWNERSHIP_PROOF);
        $linkedAmenityIds = array_values(array_map('intval', $property->amenities->modelKeys()));

        return [
            'property' => [
                ...$this->summary($property),
                'description' => $property->description,
                'property_type' => $property->property_type->value,
                'management_mode' => $property->management_mode->value,
                'pause_reason' => $property->pause_reason?->label(),
                'owner_email' => $property->owner->email,
                'street' => $property->street,
                'exterior_number' => $property->exterior_number,
                'interior_number' => $property->interior_number,
                'neighborhood' => $property->neighborhood,
                'city' => $property->city,
                'state' => $property->state,
                'postal_code' => $property->postal_code,
                'latitude' => $property->latitude !== null ? (float) $property->latitude : null,
                'longitude' => $property->longitude !== null ? (float) $property->longitude : null,
                'gender_policy' => $property->gender_policy->value,
                'allowed_communities' => $property->allowed_communities,
                'amenity_ids' => $linkedAmenityIds,
                'house_rules' => $property->houseRules->map(fn (HouseRule $rule): array => [
                    'id' => $rule->id,
                    'is_allowed' => (bool) $rule->getRelationValue('pivot')?->getAttribute('is_allowed'),
                    'note' => $rule->getRelationValue('pivot')?->getAttribute('note'),
                ])->values(),
                'inspection_valid_until' => $property->inspection_valid_until?->toDateString(),
                'submitted_for_inspection_at' => $property->submitted_for_inspection_at?->toDateString(),
                'passed_inspection' => $property->status->hasPassedInspection(),
                'editable' => $property->status->value !== 'archived',
            ],
            'units' => $property->units->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'parent_id' => $unit->parent_id,
                'type' => $unit->type->value,
                'type_label' => $unit->type->label(),
                'name' => $unit->name,
                'description' => $unit->description,
                'capacity' => $unit->capacity,
                'is_rentable' => $unit->is_rentable,
                'gender_policy' => $unit->gender_policy?->value,
                'allowed_communities' => $unit->allowed_communities,
                'effective_gender_policy' => $unit->effectiveGenderPolicy()->label(),
                'amenity_ids' => $unit->amenities->pluck('id'),
            ])->values(),
            'photos' => $property->getMedia(Property::PHOTOS)->map(fn (Media $photo): array => [
                'id' => $photo->id,
                'thumb' => $this->photoUrl($photo, 'thumb'),
                'url' => $this->photoUrl($photo, 'large'),
                'alt' => $photo->getCustomProperty('alt'),
                'unit_id' => $photo->getCustomProperty('unit_id'),
            ])->values(),
            'ownershipProof' => $proof ? [
                'name' => $proof->file_name,
                'url' => $proof->getTemporaryUrl(now()->addMinutes(5)),
                'uploaded_at' => $proof->created_at?->toDateString(),
            ] : null,
            'publication' => $this->publicationRequirements->handle($property),
            'can' => [
                'update' => $viewer->can('update', $property),
                'publish' => $viewer->can('publish', $property),
                'archive' => $viewer->can('archive', $property),
                'requestInspection' => $viewer->can('requestInspection', $property),
            ],
            'options' => [
                ...$this->formOptions(),
                'unitTypes' => array_map(fn (UnitType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'can_be_root' => $type->canBeRoot(),
                    'children' => array_map(fn (UnitType $child): string => $child->value, $type->allowedChildren()),
                ], UnitType::cases()),
                'genderPolicies' => GenderPolicy::options(),
                'communities' => CommunityType::options(),
                'templates' => UnitTemplate::options(),
                'propertyAmenities' => $this->amenities(AmenityScope::forProperty(), $linkedAmenityIds),
                'unitAmenities' => $this->amenities(AmenityScope::forUnit(), []),
                'houseRules' => HouseRule::query()->active()->orderBy('name')->get(['id', 'name', 'description']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'propertyTypes' => PropertyType::options(),
            'managementModes' => ManagementMode::options(),
        ];
    }

    /**
     * @param  list<AmenityScope>  $scopes
     * @param  list<int>  $alwaysInclude
     * @return list<array{value: int, label: string, category: string}>
     */
    private function amenities(array $scopes, array $alwaysInclude): array
    {
        return array_values(Amenity::query()
            ->availableFor($scopes)
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $alwaysInclude))
            ->orderBy('category')->orderBy('name')->get()
            ->map(fn (Amenity $amenity): array => ['value' => $amenity->id, 'label' => $amenity->name, 'category' => $amenity->category->label()])
            ->all());
    }

    private function photoUrl(Media $photo, string $conversion): string
    {
        return $photo->hasGeneratedConversion($conversion) ? $photo->getUrl($conversion) : $photo->getUrl();
    }
}
