<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Payments\Actions\MarkOverdueCharges;
use App\Modules\Payments\Enums\SimulatedOutcome;
use App\Modules\Payments\Models\PaymentSimulationSetting;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\Notification;

/**
 * HU-09.03 · Saldo del propietario, HU-09.04 · Panel financiero, HU-09.05 · Escenarios.
 */
beforeEach(fn () => Notification::fake());

function paidRental(array $attributes = []): Rental
{
    $rental = approvedRental(attributes: $attributes);
    test()->actingAs($rental->client)->post(route('charges.pay', [$rental, $rental->charges()->where('number', 1)->firstOrFail()]));

    return $rental->refresh();
}

it('shows the owner gross rent, commission and net of their own rentals (HU-09.03)', function () {
    $start = now()->addDays(10)->startOfDay();
    $rental = paidRental(['periods' => 2, 'total_minor' => 900_000, 'ends_on' => $start->copy()->addMonthsNoOverflow(2)->toDateString()]);
    paidRental();

    $this->actingAs($rental->property->owner)->get(route('payments.balance'))
        ->assertInertia(fn ($page) => $page
            ->component('payments/balance')
            ->where('totals.gross_minor', 450_000)
            ->where('totals.commission_minor', 45_000)
            ->where('totals.net_minor', 405_000)
            ->where('totals.deposits_minor', 450_000)
            ->where('totals.upcoming_minor', 450_000)
            ->has('rentals', 1)
            ->where('rentals.0.folio', $rental->folio));
});

it('keeps the income page away from clients', function () {
    $this->actingAs(verifiedClient())->get(route('payments.balance'))->assertForbidden();
});

it('shows administrators commissions, owner balances, deposits and overdue charges (HU-09.04)', function () {
    $start = now()->addDays(10)->startOfDay();
    $rental = paidRental(['periods' => 2, 'total_minor' => 900_000, 'ends_on' => $start->copy()->addMonthsNoOverflow(2)->toDateString()]);
    $rental->charges()->where('number', 2)->update(['due_on' => now()->subDay()]);
    app(MarkOverdueCharges::class)->handle();

    $this->actingAs(userWithRole(Role::Admin))->get(route('payments.finance'))
        ->assertInertia(fn ($page) => $page
            ->component('payments/finance')
            ->where('totals.commission_minor', 45_000)
            ->where('totals.owner_payable_minor', 405_000)
            ->where('totals.deposits_minor', 450_000)
            ->where('totals.overdue_minor', 450_000)
            ->where('owners.0.id', $rental->property->owner_id)
            ->where('overdue.0.folio', $rental->folio)
            ->where('payments.0.status', 'succeeded'));
});

it('keeps the financial panel for administrators', function () {
    $this->actingAs(verifiedOwner())->get(route('payments.finance'))->assertForbidden();
});

it('lets the Super Admin choose the test gateway scenario (HU-09.05, RN-72)', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);

    $this->actingAs($superAdmin)->put(route('payment-simulation.update'), ['outcome' => 'decline', 'delay_seconds' => 30])->assertSessionHasNoErrors();

    expect(PaymentSimulationSetting::current()->outcome)->toBe(SimulatedOutcome::Decline);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'payment', 'event' => 'simulation_configured', 'causer_id' => $superAdmin->id]);

    $this->actingAs(userWithRole(Role::Admin))->put(route('payment-simulation.update'), ['outcome' => 'approve', 'delay_seconds' => 30])->assertForbidden();
});
