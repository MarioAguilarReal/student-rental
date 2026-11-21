<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Enums\EntryDirection;
use App\Modules\Payments\Enums\LedgerAccount;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Payments\Enums\SimulatedOutcome;
use App\Modules\Payments\Jobs\DeliverSimulatedPaymentResult;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Models\LedgerEntry;
use App\Modules\Payments\Models\PaymentAttempt;
use App\Modules\Payments\Models\PaymentSimulationSetting;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Rentals\Actions\ExpireRentals;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

/**
 * HU-09.01 · Pagar un cargo (RN-71, RN-72) and HU-09.02 · Contabilidad (RN-73, RN-75).
 */
beforeEach(fn () => Notification::fake());

function firstCharge(Rental $rental): Charge
{
    return $rental->charges()->where('number', 1)->firstOrFail();
}

function payCharge(Rental $rental, Charge $charge): TestResponse
{
    return test()->actingAs($rental->client)->post(route('charges.pay', [$rental, $charge]));
}

function simulate(SimulatedOutcome $outcome, int $delaySeconds = 30): void
{
    PaymentSimulationSetting::query()->updateOrCreate([], ['outcome' => $outcome, 'delay_seconds' => $delaySeconds]);
}

it('confirms the rental when the client pays the first charge (RN-71)', function () {
    $rental = approvedRental();
    $charge = firstCharge($rental);

    payCharge($rental, $charge)->assertSessionHasNoErrors();

    $attempt = PaymentAttempt::query()->sole();
    expect($charge->fresh()?->status)->toBe(ChargeStatus::Paid)
        ->and($charge->fresh()?->paid_at)->not->toBeNull()
        ->and($attempt->status)->toBe(PaymentAttemptStatus::Succeeded)
        ->and($attempt->gateway)->toBe('simulated')
        ->and($attempt->reference)->toStartWith('SIM-')
        ->and($attempt->amount_minor)->toBe(900_000)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Confirmed);
    Notification::assertSentTo($rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'charge.paid');
    $this->assertDatabaseHas('activity_log', ['log_name' => 'payment', 'event' => 'paid', 'subject_id' => $charge->id]);
});

it('tells the owner of a managed property that the rental is confirmed (HU-10.01)', function () {
    $admin = userWithRole(Role::Admin);
    $rental = approvedRental(['management_mode' => ManagementMode::Managed], ['commission_bp' => 2000]);

    payCharge($rental, firstCharge($rental));

    Notification::assertSentTo([$rental->client, $rental->property->owner, $admin], PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.confirmed');
});

it('posts a balanced transaction: commission, owner and deposit (RN-73, RN-51, RN-52)', function (ManagementMode $mode, int $basisPoints, int $commission) {
    $rental = approvedRental(['management_mode' => $mode], ['commission_bp' => $basisPoints]);

    payCharge($rental, firstCharge($rental));

    $entries = LedgerEntry::query()->get();
    $credit = fn (LedgerAccount $account) => $entries->where('account', $account)->where('direction', EntryDirection::Credit)->sum('amount_minor');

    expect($entries->pluck('transaction_id')->unique())->toHaveCount(1)
        ->and($entries->where('direction', EntryDirection::Debit)->sum('amount_minor'))->toBe($entries->where('direction', EntryDirection::Credit)->sum('amount_minor'))
        ->and($entries->firstWhere('account', LedgerAccount::Client)?->amount_minor)->toBe(900_000)
        ->and($credit(LedgerAccount::PlatformCommission))->toBe($commission)
        ->and($credit(LedgerAccount::OwnerPayable))->toBe(450_000 - $commission)
        ->and($credit(LedgerAccount::DepositsHeld))->toBe(450_000)
        ->and($entries->firstWhere('account', LedgerAccount::OwnerPayable)?->holder_id)->toBe($rental->property->owner_id);
})->with([
    'self-managed · 10 %' => [ManagementMode::SelfManaged, 1000, 45_000],
    'managed · 20 %' => [ManagementMode::Managed, 2000, 90_000],
]);

it('never edits nor deletes a ledger entry (RN-75)', function () {
    $rental = approvedRental();
    payCharge($rental, firstCharge($rental));
    $entry = LedgerEntry::query()->firstOrFail();

    expect(fn () => $entry->forceFill(['amount_minor' => 1])->save())->toThrow(QueryException::class);
});

it('lets the client retry a declined payment (RN-72)', function () {
    $rental = approvedRental();
    $charge = firstCharge($rental);
    simulate(SimulatedOutcome::Decline);

    payCharge($rental, $charge)->assertSessionHasNoErrors();

    expect($charge->fresh()?->status)->toBe(ChargeStatus::Failed)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Approved)
        ->and(LedgerEntry::query()->count())->toBe(0);
    Notification::assertSentTo($rental->client, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'charge.failed');

    simulate(SimulatedOutcome::Approve);
    payCharge($rental, $charge)->assertSessionHasNoErrors();

    expect($charge->fresh()?->status)->toBe(ChargeStatus::Paid)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Confirmed)
        ->and(PaymentAttempt::query()->pluck('status')->all())->toEqualCanonicalizing([PaymentAttemptStatus::Failed, PaymentAttemptStatus::Succeeded]);
});

it('waits for a late answer without paying twice nor expiring meanwhile (RN-72, D-75)', function () {
    Queue::fake();
    $rental = approvedRental();
    $charge = firstCharge($rental);
    simulate(SimulatedOutcome::Delay, 60);

    payCharge($rental, $charge)->assertSessionHasNoErrors();

    $attempt = PaymentAttempt::query()->sole();
    expect($attempt->status)->toBe(PaymentAttemptStatus::Processing)
        ->and($charge->fresh()?->status)->toBe(ChargeStatus::Pending);

    payCharge($rental, $charge)->assertSessionHasErrors(['domain' => 'Ya se está procesando un pago de este cargo.']);

    $this->travel(49)->hours();
    app(ExpireRentals::class)->handle();
    expect($rental->fresh()?->status)->toBe(RentalStatus::Approved);

    Queue::assertPushed(DeliverSimulatedPaymentResult::class, function (DeliverSimulatedPaymentResult $job) {
        app()->call([$job, 'handle']);

        return true;
    });

    expect($attempt->fresh()?->status)->toBe(PaymentAttemptStatus::Succeeded)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Confirmed);
});

it('refuses to pay once the 48 hours ran out (RN-62)', function () {
    $rental = approvedRental();
    $this->travel(49)->hours();

    payCharge($rental, firstCharge($rental))->assertSessionHasErrors(['domain' => 'Se terminó el plazo para pagar esta renta.']);
});

it('takes the charges in order and never twice (D-75)', function () {
    $start = now()->addDays(10)->startOfDay();
    $rental = approvedRental(attributes: ['periods' => 3, 'total_minor' => 1_350_000, 'ends_on' => $start->copy()->addMonthsNoOverflow(3)->toDateString()]);
    payCharge($rental, firstCharge($rental));
    $charges = $rental->charges()->orderBy('number')->get();

    payCharge($rental, $charges[0])->assertSessionHasErrors(['domain' => 'Este cargo ya está pagado.']);
    payCharge($rental, $charges[2])->assertSessionHasErrors(['domain' => 'Paga primero el cargo 2.']);
    payCharge($rental, $charges[1])->assertSessionHasNoErrors();

    expect($charges[1]->fresh()?->status)->toBe(ChargeStatus::Paid)
        ->and($rental->fresh()?->status)->toBe(RentalStatus::Confirmed);
});

it('lets only the client of the rental pay', function () {
    $rental = approvedRental();
    $charge = firstCharge($rental);

    $this->actingAs(verifiedClient())->post(route('charges.pay', [$rental, $charge]))->assertForbidden();
    $this->actingAs($rental->property->owner)->post(route('charges.pay', [$rental, $charge]))->assertForbidden();
    $this->actingAs(userWithRole(Role::Admin))->post(route('charges.pay', [$rental, $charge]))->assertForbidden();

    expect(PaymentAttempt::query()->count())->toBe(0);
});

it('refuses charges of a cancelled rental', function () {
    $rental = approvedRental();
    $this->actingAs(userWithRole(Role::Admin))->post(route('rentals.cancel', $rental), ['reason' => 'Duplicada.']);

    payCharge($rental, firstCharge($rental))->assertSessionHasErrors(['domain' => 'Este cargo fue cancelado.']);
});

it('offers the client the "Pay" button only on the charge they can pay now', function () {
    $rental = approvedRental();

    $this->actingAs($rental->client)->get(route('rentals.show', $rental))
        ->assertInertia(fn ($page) => $page->where('rental.charges.0.payable', true));

    $this->actingAs($rental->property->owner)->get(route('rentals.show', $rental))
        ->assertInertia(fn ($page) => $page->where('rental.charges.0.payable', false));
});
