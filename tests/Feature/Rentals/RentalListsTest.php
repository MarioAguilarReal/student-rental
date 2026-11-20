<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;

/**
 * HU-08.04 · Mis rentas (cliente) and HU-08.05 · Rentas de mis propiedades.
 */
function rentalOf(array $property = [], array $attributes = []): Rental
{
    $listed = listedProperty(['owner_id' => verifiedOwner()->id, 'street' => 'Calle Secreta', 'exterior_number' => '123', ...$property]);

    return Rental::factory()->create([
        'unit_id' => $listed->units()->firstOrFail()->id,
        'client_id' => verifiedClient()->id,
        'management_mode' => $listed->management_mode,
        ...$attributes,
    ]);
}

it('lists only the client\'s own rentals', function () {
    $mine = rentalOf();
    rentalOf();

    $this->actingAs($mine->client)->get(route('rentals.index'))
        ->assertInertia(fn ($page) => $page->component('rentals/index')->has('rentals.data', 1)->where('rentals.data.0.id', $mine->id));
});

it('shows the exact address and the host\'s contact only once the rental is confirmed', function (RentalStatus $status, bool $revealed) {
    $rental = rentalOf();
    $rental->update(['status' => $status]);

    $this->actingAs($rental->client)->get(route('rentals.show', $rental))
        ->assertInertia(fn ($page) => $page
            ->where('rental.address', $revealed ? $rental->property->fullAddress() : null)
            ->where('rental.contact', $revealed ? ['name' => $rental->property->owner->name, 'email' => $rental->property->owner->email] : null));
})->with([
    'requested' => [RentalStatus::Requested, false],
    'approved' => [RentalStatus::Approved, false],
    'confirmed' => [RentalStatus::Confirmed, true],
    'active' => [RentalStatus::Active, true],
]);

it('hides a rental from other clients', function () {
    $rental = rentalOf();

    $this->actingAs(verifiedClient())->get(route('rentals.show', $rental))->assertForbidden();
});

it('shows owners the rentals of their properties only, by status', function () {
    $rental = rentalOf();
    rentalOf();

    $this->actingAs($rental->property->owner)->get(route('rentals.manage', ['status' => 'requested']))
        ->assertInertia(fn ($page) => $page->component('rentals/manage')
            ->has('rentals.data', 1)
            ->where('rentals.data.0.id', $rental->id)
            ->where('statusCounts.requested', 1));
});

it('lets the owner of a managed property read its rentals without deciding (HU-08.05)', function () {
    $rental = rentalOf(['management_mode' => ManagementMode::Managed]);

    $this->actingAs($rental->property->owner)->get(route('rentals.show', $rental))
        ->assertInertia(fn ($page) => $page->where('canDecide', false)->where('rental.managed', true));
    $this->actingAs($rental->property->owner)->post(route('rentals.approve', $rental))->assertForbidden();
});

it('shows administrators every rental', function () {
    rentalOf();
    rentalOf();

    $this->actingAs(userWithRole(Role::Admin))->get(route('rentals.manage'))
        ->assertInertia(fn ($page) => $page->has('rentals.data', 2));
});

it('keeps clients out of the operators\' list', function () {
    $this->actingAs(verifiedClient())->get(route('rentals.manage'))->assertForbidden();
});
