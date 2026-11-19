<?php

namespace App\Modules\Inspections\Actions;

use App\Models\User;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;

/**
 * Only the inspector the inspection was assigned to works on it, and only while it is open.
 */
class EnsureAssignedInspector
{
    public static function check(User $inspector, Inspection $inspection, InspectionStatus $expected): void
    {
        (new self)->handle($inspector, $inspection, $expected);
    }

    public function handle(User $inspector, Inspection $inspection, InspectionStatus $expected): void
    {
        if ($inspection->inspector_id !== $inspector->id) {
            throw new BusinessRuleViolation('RN-81', Text::of('This inspection is assigned to another inspector.'));
        }

        if ($inspection->status !== $expected) {
            throw new BusinessRuleViolation('RN-80', Text::of('The inspection is :status.', ['status' => $inspection->status->label()]), 'status');
        }
    }
}
