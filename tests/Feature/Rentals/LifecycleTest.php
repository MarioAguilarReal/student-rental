<?php

use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Actions\AdvanceRentals;
use App\Modules\Rentals\Actions\ConfirmRental;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\Notification;

/**
 * RN-71 / RN-24 (confirmation) and HU-08.06 · Ciclo automático (RN-65).
 */
beforeEach(fn () => Notification::fake());

it('confirms an approved rental and rejects the requests that compete for its family and dates (RN-24)', function () {
    $property = listedProperty(['owner_id' => verifiedOwner()->id]);
    $house = $property->units()->firstOrFail();
    $room = Unit::factory()->childOf($house, UnitType::PrivateRoom)->create();
    $approved = Rental::factory()->status(RentalStatus::Approved)->create(['unit_id' => $house->id]);
    $competing = Rental::factory()->create(['unit_id' => $room->id, 'starts_on' => $approved->starts_on->addDays(3), 'ends_on' => $approved->ends_on->addDays(3)]);
    $later = Rental::factory()->create(['unit_id' => $room->id, 'starts_on' => $approved->ends_on, 'ends_on' => $approved->ends_on->addMonth()]);

    app(ConfirmRental::class)->handle($approved);

    expect($approved->fresh()?->status)->toBe(RentalStatus::Confirmed)
        ->and($competing->fresh()?->status)->toBe(RentalStatus::Rejected)
        ->and($competing->fresh()?->rejection_reason)->toBe('Sin disponibilidad: el espacio se rentó para esas fechas.')
        ->and($later->fresh()?->status)->toBe(RentalStatus::Requested);
    Notification::assertSentTo($competing->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.rejected');
    Notification::assertSentTo($approved->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.confirmed');
});

it('starts rentals on their move-in date and completes them on their move-out date (RN-65)', function () {
    $starting = Rental::factory()->status(RentalStatus::Confirmed)->between(now()->toDateString(), now()->addMonth()->toDateString())->create();
    $ending = Rental::factory()->status(RentalStatus::Active)->between(now()->subMonth()->toDateString(), now()->toDateString())->create();
    $future = Rental::factory()->status(RentalStatus::Confirmed)->create();

    expect(app(AdvanceRentals::class)->handle())->toBe(['started' => 1, 'completed' => 1])
        ->and($starting->fresh()?->status)->toBe(RentalStatus::Active)
        ->and($ending->fresh()?->status)->toBe(RentalStatus::Completed)
        ->and($future->fresh()?->status)->toBe(RentalStatus::Confirmed)
        ->and(app(AdvanceRentals::class)->handle())->toBe(['started' => 0, 'completed' => 0]);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'rental', 'event' => 'active', 'subject_id' => $starting->id]);
});
