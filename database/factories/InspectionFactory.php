<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Inspections\Models\ChecklistTemplate;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    protected $model = Inspection::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory()->withAddress()->status(PropertyStatus::PendingInspection),
            'type' => InspectionType::Initial,
            'checklist_template_id' => fn () => ChecklistTemplate::current()?->id,
            'status' => InspectionStatus::PendingAssignment,
        ];
    }

    public function assignedTo(?User $inspector = null): static
    {
        return $this->state(fn () => [
            'status' => InspectionStatus::Assigned,
            'inspector_id' => ($inspector ?? User::factory()->create()->assignRole(Role::Inspector->value))->id,
            'scheduled_for' => now()->addDays(2)->toDateString(),
        ]);
    }

    public function inProgress(?User $inspector = null): static
    {
        return $this->assignedTo($inspector)->state(['status' => InspectionStatus::InProgress, 'started_at' => now()]);
    }
}
