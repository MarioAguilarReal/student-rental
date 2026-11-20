<?php

namespace App\Modules\Properties\Actions;

use App\Models\User;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Events\PropertyArchived;
use App\Modules\Properties\Models\Property;
use App\Modules\Rentals\Contracts\RentalOccupancy;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;

/**
 * HU-04.08 · RN-13: "delete" means archive; the record and its history are kept.
 * A published property must be paused first, and it cannot have approved, confirmed or
 * active rentals.
 */
class ArchiveProperty
{
    public function __construct(private RentalOccupancy $rentals) {}

    public function handle(User $actor, Property $property): Property
    {
        $property->status->assertCanTransitionTo(PropertyStatus::Archived);

        if ($this->rentals->hasBlockingRentals($property)) {
            throw new BusinessRuleViolation('RN-13', Text::of('This property has approved, confirmed or active rentals and cannot be archived.'));
        }

        $property->forceFill(['status' => PropertyStatus::Archived, 'archived_at' => now()])->save();

        PropertyArchived::dispatch($property, $actor);

        return $property;
    }
}
