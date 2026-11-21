<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Actions\SuggestPrices;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;
use Illuminate\Support\Facades\Notification;

/**
 * HU-03.05 · Parámetros del sistema.
 */
function parameterForm(array $overrides = []): array
{
    $values = [];

    foreach ([...Parameters::all(), ...$overrides] as $key => $value) {
        data_set($values, $key, $value);
    }

    return ['values' => $values];
}

it('uses the defaults of the specification until someone changes them', function () {
    expect(Parameters::int(SystemParameter::RentalRequestHours))->toBe(72)
        ->and(Parameters::int(SystemParameter::RentalPaymentHours))->toBe(48)
        ->and(Parameters::decimal(SystemParameter::WeeklyPriceFactor))->toBe(1.15);
});

it('lets the Super Admin change a deadline, which applies to new requests and is audited', function () {
    Notification::fake();
    $superAdmin = userWithRole(Role::SuperAdmin);

    $this->actingAs($superAdmin)->put(route('parameters.update'), parameterForm(['rentals.request_hours' => 24]))
        ->assertSessionHasNoErrors();

    expect(Rental::requestHours())->toBe(24);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'settings', 'event' => 'parameters_updated', 'causer_id' => $superAdmin->id]);

    $this->freezeSecond();
    $property = listedProperty();
    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property))->assertSessionHasNoErrors();

    expect(Rental::query()->sole()->expires_at->equalTo(now()->addHours(24)))->toBeTrue();
    $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('deadlines.request_hours', 24));
});

it('changes the suggested price factors (RN-41)', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin))->put(route('parameters.update'), parameterForm(['pricing.weekly_factor' => 1.0]));

    expect(app(SuggestPrices::class)->handle(400_000)['weekly'])->toBe(100_000);
});

it('keeps every value within its range', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin))->put(route('parameters.update'), parameterForm(['rentals.payment_hours' => 0]))
        ->assertSessionHasErrors(['values.rentals.payment_hours' => 'Plazo para pagar el primer cargo debe estar entre 1 y 336.']);

    expect(Rental::paymentHours())->toBe(48);
});

it('is only for the Super Admin', function () {
    $this->actingAs(userWithRole(Role::Admin))->get(route('parameters.edit'))->assertForbidden();
    $this->actingAs(userWithRole(Role::SuperAdmin))->get(route('parameters.edit'))
        ->assertInertia(fn ($page) => $page->component('catalog/parameters')->has('parameters', count(SystemParameter::cases())));
});
