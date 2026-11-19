<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Events\PropertySubmittedForInspection;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use App\Modules\Verification\Enums\VerificationKind;

/**
 * HU-04.06 · RN-06: needs the owner's verified identity, the proof of ownership, the full
 * address with its map pin and at least one space. The Inspections module (Iteración 4)
 * picks the request up from the PropertySubmittedForInspection event.
 */
class SubmitPropertyForInspection
{
    public function handle(Property $property): Property
    {
        $property->status->assertCanTransitionTo(PropertyStatus::PendingInspection);

        $missing = array_filter([
            $property->owner->verificationProfile(VerificationKind::Owner)?->isVerified() ? null : Text::of('The owner must verify their identity.'),
            $property->hasMedia(Property::OWNERSHIP_PROOF) ? null : Text::of('Attach the proof of ownership.'),
            $property->hasCompleteAddress() ? null : Text::of('Complete the address and place the pin on the map.'),
            $property->units()->exists() ? null : Text::of('Add at least one space.'),
        ]);

        if ($missing !== []) {
            throw new BusinessRuleViolation('RN-06', implode(' ', $missing));
        }

        $reason = $property->status === PropertyStatus::Rejected ? 'resubmission' : 'initial';

        $property->forceFill([
            'status' => PropertyStatus::PendingInspection,
            'submitted_for_inspection_at' => now(),
        ])->save();

        PropertySubmittedForInspection::dispatch($property, $reason);

        return $property;
    }
}
