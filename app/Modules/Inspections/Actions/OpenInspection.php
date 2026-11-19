<?php

namespace App\Modules\Inspections\Actions;

use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Inspections\Models\ChecklistTemplate;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;

/**
 * RN-80 / RN-82: opens an inspection with the checklist version in force. A property has at
 * most one open inspection (also enforced by a partial unique index); asking again reuses it.
 */
class OpenInspection
{
    public function handle(Property $property, InspectionType $type): Inspection
    {
        $open = Inspection::query()
            ->where('property_id', $property->id)
            ->whereIn('status', InspectionStatus::open())
            ->first();

        if ($open) {
            return $open;
        }

        $template = ChecklistTemplate::current()
            ?? throw new BusinessRuleViolation('RN-82', Text::of('There is no published inspection checklist.'));

        return Inspection::create([
            'property_id' => $property->id,
            'type' => $type,
            'checklist_template_id' => $template->id,
            'status' => InspectionStatus::PendingAssignment,
        ]);
    }
}
