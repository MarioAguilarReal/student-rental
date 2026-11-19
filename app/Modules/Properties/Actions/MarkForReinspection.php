<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Events\PropertySubmittedForInspection;
use App\Modules\Properties\Models\Property;

/**
 * RN-11 · RN-12: a critical change (location, units added/removed, unit type or capacity) on a
 * property that already passed inspection sends it back to inspection and hides it. Confirmed
 * rentals are not affected. Returns whether the status changed.
 */
class MarkForReinspection
{
    public function handle(Property $property): bool
    {
        if (! $property->status->hasPassedInspection()) {
            return false;
        }

        $property->status->assertCanTransitionTo(PropertyStatus::PendingInspection);
        $property->forceFill([
            'status' => PropertyStatus::PendingInspection,
            'pause_reason' => null,
            'submitted_for_inspection_at' => now(),
        ])->save();

        PropertySubmittedForInspection::dispatch($property, 'change');

        return true;
    }
}
