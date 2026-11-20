<?php

use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;

/**
 * How rentals affect the catalog, date blocks, archiving and spaces (RN-13, RN-22, RN-26, D-66, D-69).
 */
it('shows the dates held by rentals as unavailable in the listing, and ignores plain requests (D-66)', function () {
    $property = listedProperty();
    $unit = $property->units()->firstOrFail();
    Rental::factory()->status(RentalStatus::Confirmed)->between(now()->addDays(5)->toDateString(), now()->addDays(40)->toDateString())->create(['unit_id' => $unit->id]);
    Rental::factory()->between(now()->addDays(60)->toDateString(), now()->addDays(90)->toDateString())->create(['unit_id' => $unit->id]);

    $this->get(route('stays.show', $property))->assertInertia(fn ($page) => $page
        ->has('units.0.unavailable', 1)
        ->where('units.0.unavailable.0.starts_on', now()->addDays(5)->toDateString()));
});

it('does not hold dates with an approval that ran out of time (RN-23)', function () {
    $property = listedProperty();
    Rental::factory()->status(RentalStatus::Approved)->create(['unit_id' => $property->units()->firstOrFail()->id, 'expires_at' => now()->subMinute()]);

    $this->get(route('stays.show', $property))->assertInertia(fn ($page) => $page->has('units.0.unavailable', 0));
});

it('refuses to block dates that are rented (RN-26)', function () {
    $owner = verifiedOwner();
    $property = listedProperty(['owner_id' => $owner->id]);
    $house = $property->units()->firstOrFail();
    $room = Unit::factory()->childOf($house, UnitType::PrivateRoom)->create();
    Rental::factory()->status(RentalStatus::Active)->between(now()->subDays(5)->toDateString(), now()->addDays(25)->toDateString())->create(['unit_id' => $room->id]);
    $property->update(['status' => PropertyStatus::Paused]);

    $this->actingAs($owner)->post(route('pricing.blocks.store', $property), [
        'unit_id' => $house->id, 'starts_on' => now()->addDays(10)->toDateString(), 'ends_on' => now()->addDays(12)->toDateString(), 'reason' => 'maintenance',
    ])->assertSessionHasErrors(['starts_on' => 'Esas fechas ya están rentadas.']);
});

it('refuses to archive a property with rentals under way (RN-13)', function () {
    $owner = verifiedOwner();
    $property = listedProperty(['owner_id' => $owner->id, 'status' => PropertyStatus::Paused]);
    Rental::factory()->status(RentalStatus::Confirmed)->create(['unit_id' => $property->units()->firstOrFail()->id]);

    $this->actingAs($owner)->post(route('properties.archive', $property))
        ->assertSessionHasErrors(['domain' => 'Esta propiedad tiene rentas aprobadas, confirmadas o activas y no se puede archivar.']);

    expect($property->fresh()?->status)->toBe(PropertyStatus::Paused);
});

it('keeps spaces with rental history instead of removing them (D-69)', function () {
    $owner = verifiedOwner();
    $property = listedProperty(['owner_id' => $owner->id, 'status' => PropertyStatus::Paused]);
    $unit = $property->units()->firstOrFail();
    Rental::factory()->status(RentalStatus::Completed)->create(['unit_id' => $unit->id]);

    $this->actingAs($owner)->delete(route('properties.units.destroy', [$property, $unit]))
        ->assertSessionHasErrors(['domain' => 'Este espacio tiene rentas, así que no se puede eliminar. Márcalo como no rentable.']);

    expect($unit->fresh())->not->toBeNull();
});
