<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Payments\Actions\GenerateCharges;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\Notification;

/**
 * HU-08.07 · Cancelación administrativa (RN-66).
 */
beforeEach(fn () => Notification::fake());

it('lets an administrator cancel with a reason: unpaid charges are cancelled and both parties notified', function () {
    $property = listedProperty(['owner_id' => verifiedOwner()->id]);
    $rental = Rental::factory()->status(RentalStatus::Approved)->create(['unit_id' => $property->units()->firstOrFail()->id]);
    app(GenerateCharges::class)->handle($rental);
    Charge::query()->where('number', 1)->update(['status' => ChargeStatus::Paid, 'paid_at' => now()]);
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->post(route('rentals.cancel', $rental), ['reason' => 'Problema con el inmueble.'])->assertSessionHasNoErrors();

    $rental->refresh();
    expect($rental->status)->toBe(RentalStatus::Cancelled)
        ->and($rental->cancelled_by)->toBe($admin->id)
        ->and($rental->cancellation_reason)->toBe('Problema con el inmueble.')
        ->and(Charge::query()->orderBy('number')->pluck('status')->all())->toBe([ChargeStatus::Paid]);
    Notification::assertSentTo($rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.cancelled');
    Notification::assertSentTo($property->owner, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.cancelled');
    $this->assertDatabaseHas('activity_log', ['log_name' => 'rental', 'event' => 'cancelled', 'subject_id' => $rental->id]);
});

it('requires a reason', function () {
    $rental = Rental::factory()->status(RentalStatus::Confirmed)->create();

    $this->actingAs(userWithRole(Role::Admin))->post(route('rentals.cancel', $rental), [])
        ->assertSessionHasErrors(['reason' => 'El campo motivo es obligatorio.']);
});

it('is only for administrators', function () {
    $owner = verifiedOwner();
    $property = listedProperty(['owner_id' => $owner->id]);
    $rental = Rental::factory()->status(RentalStatus::Confirmed)->create(['unit_id' => $property->units()->firstOrFail()->id]);

    $this->actingAs($owner)->post(route('rentals.cancel', $rental), ['reason' => 'x'])->assertForbidden();
});

it('cannot cancel a rental that is not under way', function () {
    $rental = Rental::factory()->create();

    $this->actingAs(userWithRole(Role::Admin))->post(route('rentals.cancel', $rental), ['reason' => 'x'])
        ->assertSessionHasErrors('status');
});
