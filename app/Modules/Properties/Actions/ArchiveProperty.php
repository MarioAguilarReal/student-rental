<?php

namespace App\Modules\Properties\Actions;

use App\Models\User;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Events\PropertyArchived;
use App\Modules\Properties\Models\Property;

/**
 * HU-04.08 · RN-13: "delete" means archive; the record and its history are kept.
 * A published property must be paused first.
 * TODO(Iteración 6): refuse when there are approved, confirmed or active rentals.
 */
class ArchiveProperty
{
    public function handle(User $actor, Property $property): Property
    {
        $property->status->assertCanTransitionTo(PropertyStatus::Archived);

        $property->forceFill(['status' => PropertyStatus::Archived, 'archived_at' => now()])->save();

        PropertyArchived::dispatch($property, $actor);

        return $property;
    }
}
