<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Pricing\Contracts\RatePlanCatalog;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Support\Text;
use App\Modules\Verification\Enums\VerificationKind;

/**
 * RN-10: everything a property needs before it can be published, as a checklist.
 */
class CheckPublicationRequirements
{
    public function __construct(private RatePlanCatalog $ratePlans) {}

    /**
     * @return list<array{key: string, label: string, met: bool}>
     */
    public function handle(Property $property): array
    {
        $requirements = [
            'owner_verified' => [Text::of('Owner identity verified'), (bool) $property->owner->verificationProfile(VerificationKind::Owner)?->isVerified()],
            'inspection' => [Text::of('Approved and current inspection'), in_array($property->status, [PropertyStatus::Approved, PropertyStatus::Published, PropertyStatus::Paused], true) && $property->hasValidInspection()],
            'rentable_unit' => [Text::of('At least one rentable space'), $property->units()->where('is_rentable', true)->where('status', UnitStatus::Active)->exists()],
            'rate_plan' => [Text::of('At least one active price plan'), $this->ratePlans->hasActivePlan($property)],
            'photos' => [Text::of('At least :count photos', ['count' => Property::MIN_PHOTOS_TO_PUBLISH]), $property->getMedia(Property::PHOTOS)->count() >= Property::MIN_PHOTOS_TO_PUBLISH],
        ];

        return array_map(
            fn (string $key, array $requirement): array => ['key' => $key, 'label' => $requirement[0], 'met' => $requirement[1]],
            array_keys($requirements),
            $requirements,
        );
    }

    /**
     * @return list<string>
     */
    public function missing(Property $property): array
    {
        return array_values(array_map(
            fn (array $requirement): string => $requirement['label'],
            array_filter($this->handle($property), fn (array $requirement): bool => ! $requirement['met']),
        ));
    }
}
