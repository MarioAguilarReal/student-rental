<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;

/**
 * HU-04.02 · Definir unidades (RN-20) and RN-11 · cambios críticos.
 */
function unitData(array $overrides = []): array
{
    return ['type' => 'entire_place', 'name' => 'Casa completa', 'capacity' => 4, 'is_rentable' => true, ...$overrides];
}

beforeEach(function () {
    $this->owner = userWithRole(Role::Owner);
    $this->property = Property::factory()->create(['owner_id' => $this->owner->id]);
});

it('builds the unit tree from a template', function (string $template, int $rooms, int $units, bool $rootRentable) {
    $this->actingAs($this->owner)
        ->post(route('properties.units.template', $this->property), ['template' => $template, 'rooms' => $rooms, 'capacity' => 4])
        ->assertSessionHasNoErrors();

    $root = $this->property->units()->whereNull('parent_id')->sole();
    expect($this->property->units()->count())->toBe($units)
        ->and($root->is_rentable)->toBe($rootRentable);
})->with([
    'entire place' => ['entire_place', 0, 1, true],
    'by rooms' => ['by_rooms', 3, 4, false],
    'mixed' => ['mixed', 2, 3, true],
]);

it('applies a template only when there are no spaces yet', function () {
    Unit::factory()->create(['property_id' => $this->property->id]);

    $this->actingAs($this->owner)
        ->post(route('properties.units.template', $this->property), ['template' => 'entire_place', 'rooms' => 0, 'capacity' => 2])
        ->assertSessionHasErrors('template');
});

it('enforces the unit hierarchy', function (string $parentType, ?string $childType, bool $valid) {
    $parent = $parentType === 'none' ? null : Unit::factory()->create(['property_id' => $this->property->id, 'type' => $parentType]);

    $response = $this->actingAs($this->owner)->post(
        route('properties.units.store', $this->property),
        unitData(['type' => $childType, 'name' => 'Nueva', 'parent_id' => $parent?->id]),
    );

    $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors();
})->with([
    'room inside a house' => ['entire_place', 'private_room', true],
    'shared room inside a house' => ['entire_place', 'shared_room', true],
    'bed inside a shared room' => ['shared_room', 'bed', true],
    'bed at the root' => ['none', 'bed', false],
    'bed inside a private room' => ['private_room', 'bed', false],
    'house inside a house' => ['entire_place', 'entire_place', false],
    'room inside a bed' => ['bed', 'private_room', false],
]);

it('refuses a parent from another property', function () {
    $foreign = Unit::factory()->create();

    $this->actingAs($this->owner)
        ->post(route('properties.units.store', $this->property), unitData(['type' => 'private_room', 'parent_id' => $foreign->id]))
        ->assertNotFound();
});

it('removes a unit together with its subdivisions', function () {
    $room = Unit::factory()->create(['property_id' => $this->property->id, 'type' => UnitType::SharedRoom]);
    Unit::factory()->childOf($room, UnitType::Bed)->count(2)->create();

    $this->actingAs($this->owner)->delete(route('properties.units.destroy', [$this->property, $room]))->assertSessionHasNoErrors();

    expect($this->property->units()->count())->toBe(0);
});

it('sends an inspected property back to inspection on critical unit changes (RN-11)', function (callable $change) {
    $this->property->update(['status' => PropertyStatus::Published, 'inspection_valid_until' => now()->addYear()]);
    $unit = Unit::factory()->create(['property_id' => $this->property->id]);

    $change($this, $unit);

    expect($this->property->fresh()?->status)->toBe(PropertyStatus::PendingInspection);
    $this->assertDatabaseHas('activity_log', ['event' => 'submitted_for_inspection', 'subject_id' => $this->property->id]);
})->with([
    'add a space' => [fn ($test, Unit $unit) => $test->actingAs($test->owner)->post(route('properties.units.store', $test->property), unitData(['type' => 'private_room', 'parent_id' => $unit->id, 'capacity' => 1]))],
    'change capacity' => [fn ($test, Unit $unit) => $test->actingAs($test->owner)->put(route('properties.units.update', [$test->property, $unit]), unitData(['capacity' => 6]))],
    'remove a space' => [fn ($test, Unit $unit) => $test->actingAs($test->owner)->delete(route('properties.units.destroy', [$test->property, $unit]))],
]);

it('does not require a new inspection for non-critical unit edits', function () {
    $this->property->update(['status' => PropertyStatus::Published, 'inspection_valid_until' => now()->addYear()]);
    $unit = Unit::factory()->create(['property_id' => $this->property->id]);

    $this->actingAs($this->owner)->put(route('properties.units.update', [$this->property, $unit]), unitData(['name' => 'Casa entera', 'description' => 'Luminosa']));

    expect($this->property->fresh()?->status)->toBe(PropertyStatus::Published);
});
