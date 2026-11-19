<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\PauseReason;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;

/**
 * RN-85: when the inspection expires without renewal, the property leaves the catalog.
 * Confirmed rentals continue. Idempotent.
 */
class PauseForExpiredInspection
{
    public function handle(Property $property): bool
    {
        if ($property->status !== PropertyStatus::Published) {
            return false;
        }

        $property->forceFill(['status' => PropertyStatus::Paused, 'pause_reason' => PauseReason::InspectionExpired])->save();

        return true;
    }
}
