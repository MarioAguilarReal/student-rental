<?php

namespace App\Modules\Inspections\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Events\InspectionAssigned;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;

/**
 * HU-06.01 · RN-81: an administrator assigns (or reassigns) an inspector and a date.
 * The inspector can never be the property's owner.
 */
class AssignInspection
{
    public function handle(User $actor, Inspection $inspection, User $inspector, CarbonImmutable $scheduledFor): Inspection
    {
        $inspection->status->assertCanTransitionTo(InspectionStatus::Assigned);

        if (! $inspector->hasRole(Role::Inspector->value) || $inspector->isSuspended()) {
            throw new BusinessRuleViolation('RN-81', Text::of('The selected person is not an active inspector.'), 'inspector_id');
        }

        if ($inspector->id === $inspection->property->owner_id) {
            throw new BusinessRuleViolation('RN-81', Text::of('An inspector cannot inspect their own property.'), 'inspector_id');
        }

        if ($scheduledFor->lessThan(CarbonImmutable::today())) {
            throw new BusinessRuleViolation('RN-81', Text::of('The inspection date cannot be in the past.'), 'scheduled_for');
        }

        $inspection->forceFill([
            'status' => InspectionStatus::Assigned,
            'inspector_id' => $inspector->id,
            'assigned_by' => $actor->id,
            'scheduled_for' => $scheduledFor,
        ])->save();

        InspectionAssigned::dispatch($inspection, $actor);

        return $inspection;
    }
}
