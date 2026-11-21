<?php

use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Payments\Actions\MarkOverdueCharges;
use App\Modules\Payments\Actions\RemindUpcomingCharges;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\Notification;

/**
 * RN-74 · Cargos vencidos, and the reminder before a charge is due (HU-10.01).
 */
beforeEach(fn () => Notification::fake());

/**
 * A confirmed rental whose second charge was due yesterday.
 */
function rentalWithLateCharge(): Charge
{
    $rental = approvedRental(attributes: ['periods' => 2, 'total_minor' => 900_000]);
    $rental->forceFill(['status' => RentalStatus::Confirmed, 'expires_at' => null])->save();
    $charge = $rental->charges()->where('number', 2)->firstOrFail();
    $charge->forceFill(['due_on' => now()->subDay()])->save();

    return $charge;
}

it('marks unpaid later charges as overdue and tells the client and the operator', function () {
    $charge = rentalWithLateCharge();

    expect(app(MarkOverdueCharges::class)->handle())->toBe(1)
        ->and($charge->fresh()?->status)->toBe(ChargeStatus::Overdue)
        ->and(app(MarkOverdueCharges::class)->handle())->toBe(0);

    $rental = $charge->rental;
    Notification::assertSentTo([$rental->client, $rental->property->owner], PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'charge.overdue');
});

it('leaves approved rentals alone: they expire instead (RN-62)', function () {
    $rental = approvedRental();
    $rental->charges()->update(['due_on' => now()->subDays(3)]);

    expect(app(MarkOverdueCharges::class)->handle())->toBe(0)
        ->and(Rental::query()->sole()->charges()->first()?->status)->toBe(ChargeStatus::Pending);
});

it('still lets the client pay an overdue charge', function () {
    $charge = rentalWithLateCharge();
    $rental = $charge->rental;
    $rental->charges()->where('number', 1)->update(['status' => ChargeStatus::Paid, 'paid_at' => now()]);
    app(MarkOverdueCharges::class)->handle();

    $this->actingAs($rental->client)->post(route('charges.pay', [$rental, $charge]))->assertSessionHasNoErrors();

    expect($charge->fresh()?->status)->toBe(ChargeStatus::Paid);
});

it('reminds the client once, a few days before a charge is due (HU-10.01)', function () {
    $charge = rentalWithLateCharge();
    $charge->forceFill(['due_on' => now()->addDays(3)])->save();
    $charge->rental->charges()->where('number', 1)->update(['status' => ChargeStatus::Paid, 'paid_at' => now()]);

    expect(app(RemindUpcomingCharges::class)->handle())->toBe(1)
        ->and(app(RemindUpcomingCharges::class)->handle())->toBe(0);

    Notification::assertSentTo($charge->rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'charge.due_soon');
});

it('does not remind charges further away', function () {
    $charge = rentalWithLateCharge();
    $charge->forceFill(['due_on' => now()->addDays(4)])->save();

    expect(app(RemindUpcomingCharges::class)->handle())->toBe(0);
});
