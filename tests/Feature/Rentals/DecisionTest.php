<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Payments\Enums\ChargeConcept;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Rentals\Policies\RentalPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;

/**
 * HU-08.02 · Aprobar o rechazar (RN-24, RN-25, RN-60, RN-62, RN-67, RN-70).
 */
beforeEach(fn () => Notification::fake());

/**
 * A request for the first space of a listed property.
 *
 * @param  array<string, mixed>  $property
 */
function pendingRequest(array $property = [], array $attributes = []): Rental
{
    $listed = listedProperty(['owner_id' => verifiedOwner()->id, ...$property]);

    return Rental::factory()->create([
        'unit_id' => $listed->units()->firstOrFail()->id,
        'client_id' => verifiedClient()->id,
        'management_mode' => $listed->management_mode,
        ...$attributes,
    ]);
}

it('approves a request: 48 h to pay, charge schedule and a notice to the client', function () {
    $this->freezeSecond();
    $rental = pendingRequest(attributes: ['periods' => 3, 'total_minor' => 1_350_000, 'deposit_minor' => 450_000]);
    $rental->update(['ends_on' => $rental->starts_on->addMonthsNoOverflow(3)]);

    $this->actingAs($rental->property->owner)->post(route('rentals.approve', $rental))->assertSessionHasNoErrors();

    $rental->refresh();
    expect($rental->status)->toBe(RentalStatus::Approved)
        ->and($rental->decided_by)->toBe($rental->property->owner_id)
        ->and($rental->expires_at->equalTo(now()->addHours(48)))->toBeTrue();

    $charges = Charge::query()->orderBy('number')->get();
    expect($charges)->toHaveCount(3)
        ->and($charges[0]->concept)->toBe(ChargeConcept::RentAndDeposit)
        ->and($charges[0]->rent_minor + $charges[0]->deposit_minor)->toBe(900_000)
        ->and($charges[0]->due_on->toDateString())->toBe(now()->toDateString())
        ->and($charges[2]->due_on->toDateString())->toBe($rental->starts_on->addMonthsNoOverflow(2)->toDateString())
        ->and($charges->every(fn (Charge $charge) => $charge->status === ChargeStatus::Pending))->toBeTrue();
    Notification::assertSentTo($rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.approved');
});

it('rejects a request with a reason the client reads (RN-67)', function () {
    $rental = pendingRequest();

    $this->actingAs($rental->property->owner)->post(route('rentals.reject', $rental), ['reason' => 'Ya no está disponible.'])->assertSessionHasNoErrors();

    $rental->refresh();
    expect($rental->status)->toBe(RentalStatus::Rejected)
        ->and($rental->rejection_reason)->toBe('Ya no está disponible.');
    Notification::assertSentTo($rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.rejected');
});

it('requires a reason to reject', function () {
    $rental = pendingRequest();

    $this->actingAs($rental->property->owner)->post(route('rentals.reject', $rental), ['reason' => ''])
        ->assertSessionHasErrors(['reason' => 'El campo motivo es obligatorio.']);
});

it('lets only the operator decide (RN-60)', function (ManagementMode $mode, string $decider, bool $allowed) {
    $rental = pendingRequest(['management_mode' => $mode]);
    $users = [
        'owner' => $rental->property->owner,
        'admin' => userWithRole(Role::Admin),
        'other owner' => verifiedOwner(),
        'client' => $rental->client,
    ];

    expect((new RentalPolicy)->decide($users[$decider], $rental))->toBe($allowed);
})->with([
    'self-managed · owner' => [ManagementMode::SelfManaged, 'owner', true],
    'self-managed · admin' => [ManagementMode::SelfManaged, 'admin', false],
    'self-managed · other owner' => [ManagementMode::SelfManaged, 'other owner', false],
    'self-managed · client' => [ManagementMode::SelfManaged, 'client', false],
    'managed · owner (read only)' => [ManagementMode::Managed, 'owner', false],
    'managed · admin' => [ManagementMode::Managed, 'admin', true],
]);

it('forbids approving to someone who does not operate the property', function () {
    $rental = pendingRequest();

    $this->actingAs(verifiedOwner())->post(route('rentals.approve', $rental))->assertForbidden();

    expect($rental->fresh()?->status)->toBe(RentalStatus::Requested);
});

it('refuses to approve when the space or its container is already held (RN-22, RN-24)', function () {
    $property = listedProperty(['owner_id' => verifiedOwner()->id]);
    $house = $property->units()->firstOrFail();
    $room = Unit::factory()->childOf($house, UnitType::PrivateRoom)->create();
    $roomRental = Rental::factory()->status(RentalStatus::Confirmed)->create(['unit_id' => $room->id]);
    $houseRequest = Rental::factory()->create(['unit_id' => $house->id, 'client_id' => verifiedClient()->id, 'starts_on' => $roomRental->starts_on, 'ends_on' => $roomRental->ends_on]);

    $this->actingAs($property->owner)->post(route('rentals.approve', $houseRequest))
        ->assertSessionHasErrors(['domain' => 'El espacio ya no está disponible en esas fechas.']);

    expect($houseRequest->fresh()?->status)->toBe(RentalStatus::Requested)
        ->and(Charge::query()->count())->toBe(0);
});

it('expires an approval that ran out of time and approves the next request (RN-25, RN-62)', function () {
    $first = pendingRequest();
    $first->update(['status' => RentalStatus::Approved, 'expires_at' => now()->subMinute()]);
    $second = Rental::factory()->create(['unit_id' => $first->unit_id, 'client_id' => verifiedClient()->id, 'starts_on' => $first->starts_on, 'ends_on' => $first->ends_on]);

    $this->actingAs($first->property->owner)->post(route('rentals.approve', $second))->assertSessionHasNoErrors();

    expect($first->fresh()?->status)->toBe(RentalStatus::Expired)
        ->and($second->fresh()?->status)->toBe(RentalStatus::Approved);
});

it('refuses to approve a request that already expired (RN-61)', function () {
    $rental = pendingRequest(attributes: ['expires_at' => now()->subMinute()]);

    $this->actingAs($rental->property->owner)->post(route('rentals.approve', $rental))
        ->assertSessionHasErrors(['domain' => 'Esta solicitud ya expiró.']);
});

it('refuses to approve when the client already holds those dates elsewhere (RN-64)', function () {
    $rental = pendingRequest();
    Rental::factory()->status(RentalStatus::Confirmed)->create(['client_id' => $rental->client_id, 'starts_on' => $rental->starts_on, 'ends_on' => $rental->ends_on]);

    $this->actingAs($rental->property->owner)->post(route('rentals.approve', $rental))
        ->assertSessionHasErrors(['domain' => 'El cliente ya tiene una renta en esas fechas.']);
});

it('never lets the database hold two blocking rentals of the same space on the same dates (RN-25)', function () {
    $rental = Rental::factory()->status(RentalStatus::Confirmed)->create();

    expect(fn () => Rental::factory()->status(RentalStatus::Approved)->create([
        'unit_id' => $rental->unit_id, 'starts_on' => $rental->starts_on, 'ends_on' => $rental->ends_on,
    ]))->toThrow(QueryException::class);
});

it('shows the operator who the client is, but not their documents or gender', function () {
    $rental = pendingRequest();
    $rental->client->forceFill(['cohabitation_gender' => 'female'])->save();

    $this->actingAs($rental->property->owner)->get(route('rentals.show', $rental))
        ->assertInertia(fn ($page) => $page
            ->component('rentals/show')
            ->where('canDecide', true)
            ->where('rental.applicant.name', $rental->client->name)
            ->where('rental.applicant.verified', true)
            ->where('rental.client_message', $rental->client_message)
            ->missing('rental.applicant.gender')
            ->missing('rental.applicant.documents'));
});

it('hides other owners\' rentals', function () {
    $rental = pendingRequest();

    $this->actingAs(verifiedOwner())->get(route('rentals.show', $rental))->assertForbidden();
});
