<?php

namespace App\Modules\Properties\Actions;

use App\Models\User;
use App\Modules\Properties\Enums\PauseReason;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Events\PropertyPaused;
use App\Modules\Properties\Models\Property;

/**
 * HU-04.07: hides the property from the catalog. Pending requests stay until the operator decides.
 */
class PauseProperty
{
    public function handle(User $actor, Property $property): Property
    {
        $property->status->assertCanTransitionTo(PropertyStatus::Paused);

        $property->forceFill(['status' => PropertyStatus::Paused, 'pause_reason' => PauseReason::Manual])->save();

        PropertyPaused::dispatch($property, $actor);

        return $property;
    }
}
