<?php

namespace App\Modules\Inspections\Actions;

use App\Models\User;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Models\Inspection;

/**
 * HU-06.03: the inspector arrives and starts the checklist.
 */
class StartInspection
{
    public function handle(User $inspector, Inspection $inspection): Inspection
    {
        EnsureAssignedInspector::check($inspector, $inspection, InspectionStatus::Assigned);

        $inspection->forceFill(['status' => InspectionStatus::InProgress, 'started_at' => now()])->save();

        return $inspection;
    }
}
