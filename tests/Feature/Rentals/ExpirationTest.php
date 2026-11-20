<?php

use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Payments\Actions\GenerateCharges;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Actions\ExpireRentals;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\Notification;

/**
 * HU-08.03 · Expiraciones automáticas (RN-61, RN-62).
 */
beforeEach(fn () => Notification::fake());

it('expires requests without an answer after 72 hours and tells both sides', function () {
    $property = listedProperty(['owner_id' => verifiedOwner()->id]);
    $rental = Rental::factory()->create(['unit_id' => $property->units()->firstOrFail()->id]);

    $this->travel(Rental::REQUEST_HOURS)->hours();
    $this->travel(1)->minutes();
    $expired = app(ExpireRentals::class)->handle();

    expect($expired)->toBe(1)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Expired);
    Notification::assertSentTo($rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.expired');
    Notification::assertSentTo($property->owner, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.expired');
});

it('leaves requests that are still on time', function () {
    $rental = Rental::factory()->create();

    $this->travel(Rental::REQUEST_HOURS - 1)->hours();

    expect(app(ExpireRentals::class)->handle())->toBe(0)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Requested);
});

it('expires unpaid approvals after 48 hours, cancels their charges and frees the dates', function () {
    $rental = Rental::factory()->status(RentalStatus::Approved)->create();
    app(GenerateCharges::class)->handle($rental);

    $this->travel(Rental::PAYMENT_HOURS + 1)->hours();
    app(ExpireRentals::class)->handle();

    expect($rental->fresh()?->status)->toBe(RentalStatus::Expired)
        ->and(Charge::query()->pluck('status')->unique()->all())->toBe([ChargeStatus::Cancelled])
        ->and(Rental::query()->blocking()->count())->toBe(0);
});

it('never expires confirmed rentals', function () {
    $rental = Rental::factory()->status(RentalStatus::Confirmed)->create(['expires_at' => now()->subDay()]);

    app(ExpireRentals::class)->handle();

    expect($rental->fresh()?->status)->toBe(RentalStatus::Confirmed);
});
