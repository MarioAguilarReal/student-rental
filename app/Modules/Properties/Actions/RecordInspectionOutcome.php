<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use Carbon\CarbonImmutable;

/**
 * Called by the Inspections module when a verdict is issued (HU-06.04).
 * - First or post-change inspection: pending inspection → approved / rejected.
 * - Renewal of a published or paused property: approval extends the validity (and a property
 *   paused only because its inspection expired stays paused until the owner resumes it);
 *   rejection takes it out of the catalog as rejected.
 */
class RecordInspectionOutcome
{
    public function handle(Property $property, bool $approved, ?CarbonImmutable $validUntil): Property
    {
        if ($approved) {
            $property->inspection_valid_until = $validUntil;

            if ($property->status === PropertyStatus::PendingInspection) {
                $property->status->assertCanTransitionTo(PropertyStatus::Approved);
                $property->status = PropertyStatus::Approved;
                $property->pause_reason = null;
            }
        } else {
            $property->status->assertCanTransitionTo(PropertyStatus::Rejected);
            $property->status = PropertyStatus::Rejected;
            $property->pause_reason = null;
            $property->inspection_valid_until = null;
        }

        $property->save();

        return $property;
    }
}
