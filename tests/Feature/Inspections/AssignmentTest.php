<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * HU-04.06 → HU-06.01 · Asignar inspección (RN-81) and HU-06.02 · Agenda del inspector.
 */
beforeEach(fn () => Notification::fake());

it('opens an inspection in the queue when the owner requests it, only once', function () {
    Storage::fake('local');
    $owner = verifiedOwner();
    $property = Property::factory()->withAddress()->create(['owner_id' => $owner->id]);
    Unit::factory()->create(['property_id' => $property->id]);
    $property->addMedia(UploadedFile::fake()->create('escritura.pdf', 50, 'application/pdf'))->toMediaCollection(Property::OWNERSHIP_PROOF);

    $this->actingAs($owner)->post(route('properties.inspection.request', $property))->assertSessionHasNoErrors();

    $inspection = Inspection::query()->sole();
    expect($inspection->status)->toBe(InspectionStatus::PendingAssignment)
        ->and($inspection->type)->toBe(InspectionType::Initial);
});

it('opens a "change" inspection after a critical change', function () {
    $property = Property::factory()->inspected()->create(['owner_id' => verifiedOwner()->id, 'status' => PropertyStatus::Published]);
    Unit::factory()->create(['property_id' => $property->id]);

    $this->actingAs($property->owner)->post(route('properties.units.store', $property), ['type' => 'entire_place', 'name' => 'Casa 2', 'capacity' => 2, 'is_rentable' => true]);

    expect(Inspection::query()->sole()->type)->toBe(InspectionType::Change);
});

it('assigns an inspector and a date, and notifies the inspector and the owner', function () {
    $inspection = propertyAwaitingInspection();
    $inspector = userWithRole(Role::Inspector);
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->post(route('inspections.queue.assign', $inspection), [
        'inspector_id' => $inspector->id,
        'scheduled_for' => now()->addDays(3)->toDateString(),
    ])->assertSessionHasNoErrors();

    $inspection->refresh();
    expect($inspection->status)->toBe(InspectionStatus::Assigned)
        ->and($inspection->inspector_id)->toBe($inspector->id);
    Notification::assertSentTo($inspector, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'inspection.assigned');
    Notification::assertSentTo($inspection->property->owner, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'inspection.scheduled');
    $this->assertDatabaseHas('activity_log', ['event' => 'assigned', 'subject_id' => $inspection->id]);
});

it('never assigns the owner as inspector of their own property (RN-81)', function () {
    $inspection = propertyAwaitingInspection();
    $owner = $inspection->property->owner;
    $owner->assignRole(Role::Inspector->value);

    $this->actingAs(userWithRole(Role::Admin))->post(route('inspections.queue.assign', $inspection), [
        'inspector_id' => $owner->id,
        'scheduled_for' => now()->addDay()->toDateString(),
    ])->assertSessionHasErrors('inspector_id');
});

it('rejects people who are not inspectors and dates in the past', function (callable $data, string $field) {
    $inspection = propertyAwaitingInspection();

    $this->actingAs(userWithRole(Role::Admin))
        ->post(route('inspections.queue.assign', $inspection), $data())
        ->assertSessionHasErrors($field);
})->with([
    'a client' => [fn () => ['inspector_id' => userWithRole(Role::Client)->id, 'scheduled_for' => now()->addDay()->toDateString()], 'inspector_id'],
    'yesterday' => [fn () => ['inspector_id' => userWithRole(Role::Inspector)->id, 'scheduled_for' => now()->subDay()->toDateString()], 'scheduled_for'],
]);

it('shows each inspector only their own visits', function () {
    $mine = userWithRole(Role::Inspector);
    Inspection::factory()->assignedTo($mine)->create();
    Inspection::factory()->assignedTo()->create();

    $this->actingAs($mine)->get(route('inspections.agenda'))
        ->assertInertia(fn ($page) => $page->component('inspections/agenda')->has('upcoming', 1));
});

it('filters the queue by status with counts', function () {
    Inspection::factory()->create();
    Inspection::factory()->assignedTo()->create();

    $this->actingAs(userWithRole(Role::Admin))->get(route('inspections.queue.index'))
        ->assertInertia(fn ($page) => $page
            ->where('status', 'pending_assignment')
            ->has('inspections.data', 1)
            ->where('statusCounts.assigned', 1));
});

it('keeps the queue away from owners and inspectors', function (Role $role) {
    $this->actingAs(userWithRole($role))->get(route('inspections.queue.index'))->assertForbidden();
})->with([Role::Owner, Role::Inspector]);
