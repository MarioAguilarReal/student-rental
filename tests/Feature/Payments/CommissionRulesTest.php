<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Properties\Enums\ManagementMode;
use Carbon\CarbonImmutable;

/**
 * HU-03.04 · Reglas de comisión (RN-50).
 */
function currentRule(ManagementMode $mode): CommissionRule
{
    return CommissionRule::query()->where('management_mode', $mode)->whereNull('valid_until')->sole();
}

it('schedules a new rate that closes the current one the day before', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);
    $from = now()->addMonth()->startOfMonth()->toDateString();

    $this->actingAs($superAdmin)->post(route('commission-rules.store'), [
        'management_mode' => 'self_managed', 'percentage' => '12.5', 'valid_from' => $from,
    ])->assertSessionHasNoErrors();

    $rates = app(CommissionRates::class);
    expect(currentRule(ManagementMode::SelfManaged)->percentage_bp)->toBe(1250)
        ->and($rates->basisPointsFor(ManagementMode::SelfManaged))->toBe(1000)
        ->and($rates->basisPointsFor(ManagementMode::SelfManaged, CarbonImmutable::parse($from)))->toBe(1250)
        ->and($rates->basisPointsFor(ManagementMode::Managed, CarbonImmutable::parse($from)))->toBe(2000);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'payment', 'event' => 'commission_scheduled']);
});

it('never rewrites past rates nor overlaps them', function (array $input, string $field, string $message) {
    $this->actingAs(userWithRole(Role::SuperAdmin))->post(route('commission-rules.store'), [
        'management_mode' => 'managed', 'percentage' => '15', ...$input,
    ])->assertSessionHasErrors([$field => $message]);
})->with([
    'in the past' => [fn () => ['valid_from' => now()->subDay()->toDateString()], 'valid_from', 'Una nueva tarifa puede empezar hoy como muy pronto.'],
    'over 100 %' => [fn () => ['valid_from' => now()->addDay()->toDateString(), 'percentage' => '101'], 'percentage', 'El campo comisión no debe ser mayor que 100.'],
]);

it('requires starting after the latest scheduled rate', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);
    $this->actingAs($superAdmin)->post(route('commission-rules.store'), ['management_mode' => 'managed', 'percentage' => '18', 'valid_from' => now()->addDays(10)->toDateString()]);

    $this->actingAs($superAdmin)->post(route('commission-rules.store'), ['management_mode' => 'managed', 'percentage' => '19', 'valid_from' => now()->addDays(5)->toDateString()])
        ->assertSessionHasErrors(['valid_from' => 'Debe empezar después del '.now()->addDays(10)->toDateString().', cuando empieza la tarifa más reciente.']);
});

it('removes a rate that has not started and reopens the previous one', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);
    $this->actingAs($superAdmin)->post(route('commission-rules.store'), ['management_mode' => 'managed', 'percentage' => '18', 'valid_from' => now()->addDays(10)->toDateString()]);
    $scheduled = currentRule(ManagementMode::Managed);

    $this->actingAs($superAdmin)->delete(route('commission-rules.destroy', $scheduled))->assertSessionHasNoErrors();

    expect(currentRule(ManagementMode::Managed)->percentage_bp)->toBe(2000)
        ->and(CommissionRule::query()->find($scheduled->id))->toBeNull();
});

it('refuses to remove a rate already in force', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin))->delete(route('commission-rules.destroy', currentRule(ManagementMode::Managed)))
        ->assertSessionHasErrors(['domain' => 'Solo se pueden quitar tarifas que todavía no empiezan.']);
});

it('is only for the Super Admin', function () {
    $this->actingAs(userWithRole(Role::Admin))->get(route('commission-rules.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::SuperAdmin))->get(route('commission-rules.index'))
        ->assertInertia(fn ($page) => $page->component('payments/commission-rules')->where('modes.0.rules.0.state', 'current'));
});
