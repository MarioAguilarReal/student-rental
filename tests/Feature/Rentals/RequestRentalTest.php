<?php

use App\Modules\Identity\Enums\CohabitationGender;
use App\Modules\Identity\Enums\Role;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Verification\Enums\CommunityType;
use Illuminate\Support\Facades\Notification;

/**
 * HU-08.01 · Solicitar renta.
 */
beforeEach(fn () => Notification::fake());

it('creates a request with a copy of the commercial values and notifies the owner', function () {
    $this->freezeSecond();
    $property = listedProperty(monthlyMinor: 450_000);
    $unit = $property->units()->firstOrFail();
    UnitDeposit::create(['unit_id' => $unit->id, 'amount_minor' => 300_000]);
    $client = verifiedClient();
    $start = now()->addDays(10)->startOfDay();

    $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property, ['starts_on' => $start->toDateString()]))
        ->assertRedirect(route('rentals.show', Rental::query()->sole()));

    $rental = Rental::query()->sole();
    expect($rental->status)->toBe(RentalStatus::Requested)
        ->and($rental->client_id)->toBe($client->id)
        ->and($rental->ends_on->toDateString())->toBe($start->copy()->addMonthsNoOverflow(2)->toDateString())
        ->and($rental->price_minor)->toBe(450_000)
        ->and($rental->total_minor)->toBe(900_000)
        ->and($rental->deposit_minor)->toBe(300_000)
        ->and($rental->management_mode)->toBe(ManagementMode::SelfManaged)
        ->and($rental->commission_bp)->toBe(1000)
        ->and($rental->expires_at->equalTo(now()->addHours(72)))->toBeTrue()
        ->and($rental->rules_accepted_at)->not->toBeNull()
        ->and($rental->folio)->toBe(sprintf('R-%d-%06d', now()->year, $rental->id));
    Notification::assertSentTo($property->owner, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.requested');
    $this->assertDatabaseHas('activity_log', ['log_name' => 'rental', 'event' => 'requested', 'subject_id' => $rental->id]);
});

it('keeps the copied price when the plan changes later (RN-45)', function () {
    $property = listedProperty(monthlyMinor: 450_000);
    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property));

    RatePlan::query()->update(['price_minor' => 999_000]);

    expect(Rental::query()->sole()->price_minor)->toBe(450_000);
});

it('notifies the administrators of a managed property instead of the owner', function () {
    $property = listedProperty(['management_mode' => ManagementMode::Managed]);
    $admin = userWithRole(Role::Admin);

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property))->assertSessionHasNoErrors();

    expect(Rental::query()->sole()->commission_bp)->toBe(2000);
    Notification::assertSentTo($admin, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'rental.requested');
    Notification::assertNotSentTo($property->owner, PlatformMessage::class);
});

it('refuses clients without a current verification (RN-01)', function () {
    $property = listedProperty();

    $this->actingAs(userWithRole(Role::Client))->post(route('rentals.store', $property), rentalRequest($property))
        ->assertSessionHasErrors(['domain' => 'Solo los clientes con una verificación vigente pueden solicitar una renta.']);

    expect(Rental::query()->count())->toBe(0);
});

it('forbids requesting to users without the client role', function () {
    $property = listedProperty();

    $this->actingAs(verifiedOwner())->post(route('rentals.store', $property), rentalRequest($property))->assertForbidden();
});

it('validates the format of the request', function () {
    $property = listedProperty();

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), [])
        ->assertSessionHasErrors(['unit_id', 'period', 'starts_on', 'periods']);
});

it('refuses spaces of properties that are not listed (RN-12)', function () {
    $property = listedProperty();
    $property->update(['status' => PropertyStatus::Paused]);

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property))
        ->assertSessionHasErrors(['unit_id' => 'Este espacio no está recibiendo solicitudes.']);
});

it('refuses clients whose community the space does not admit (RN-31)', function () {
    $property = listedProperty(['allowed_communities' => [CommunityType::Faculty->value]]);

    $this->actingAs(verifiedClient(CommunityType::Student))->post(route('rentals.store', $property), rentalRequest($property))
        ->assertSessionHasErrors(['unit_id' => 'Este espacio no está abierto a tu comunidad.']);
});

it('asks for the gender only when the space has a gender rule, and keeps it private (RN-30, RN-32)', function () {
    $property = listedProperty(['gender_policy' => GenderPolicy::WomenOnly]);
    $client = verifiedClient();

    $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property))
        ->assertSessionHasErrors(['gender' => 'Este espacio tiene una regla de género: indícanos tu género para fines de convivencia para solicitarlo.']);

    $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property, ['gender' => 'male']))
        ->assertSessionHasErrors(['gender' => 'La regla de convivencia de este espacio es: solo mujeres.']);

    $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property, ['gender' => 'female']))
        ->assertSessionHasNoErrors();

    expect($client->fresh()?->cohabitation_gender)->toBe(CohabitationGender::Female);
});

it('requires accepting the house rules (RN-33)', function () {
    $property = listedProperty();

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property, ['accept_rules' => false]))
        ->assertSessionHasErrors(['accept_rules' => 'Acepta las reglas de la casa para enviar tu solicitud.']);
});

it('enforces the plan, the periods and the dates (RN-43, RN-63)', function (array $overrides, string $field, string $message) {
    $property = listedProperty();

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property, $overrides))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'no weekly plan' => [['period' => 'weekly'], 'period', 'Este espacio no se ofrece con ese periodo.'],
    'too many periods' => [['periods' => 13], 'periods', 'Para este espacio elige entre 1 y 12 periodos.'],
    'starts today' => [fn () => ['starts_on' => now()->toDateString()], 'starts_on', 'La fecha de entrada debe ser a partir de mañana y dentro de los próximos 12 meses.'],
    'too far ahead' => [fn () => ['starts_on' => now()->addMonths(13)->toDateString()], 'starts_on', 'La fecha de entrada debe ser a partir de mañana y dentro de los próximos 12 meses.'],
]);

it('refuses dates that are blocked or held by another rental (RN-22)', function () {
    $property = listedProperty();
    $unit = $property->units()->firstOrFail();
    AvailabilityBlock::create([
        'unit_id' => $unit->id, 'starts_on' => now()->addDays(15)->toDateString(), 'ends_on' => now()->addDays(20)->toDateString(),
        'reason' => 'maintenance', 'created_by' => $property->owner_id,
    ]);

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property))
        ->assertSessionHasErrors(['starts_on' => 'El espacio no está disponible en esas fechas.']);
});

it('lets several requests compete for the same dates: a request does not hold them (RN-23)', function () {
    $property = listedProperty();

    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property))->assertSessionHasNoErrors();
    $this->actingAs(verifiedClient())->post(route('rentals.store', $property), rentalRequest($property))->assertSessionHasNoErrors();

    expect(Rental::query()->where('status', RentalStatus::Requested)->count())->toBe(2);
});

it('refuses a client who already holds a rental on those dates (RN-64)', function () {
    $client = verifiedClient();
    $start = now()->addDays(10)->startOfDay();
    Rental::factory()->status(RentalStatus::Confirmed)->between($start->toDateString(), $start->copy()->addMonth()->toDateString())->create(['client_id' => $client->id]);
    $property = listedProperty();

    $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property, ['starts_on' => $start->copy()->addDays(5)->toDateString()]))
        ->assertSessionHasErrors(['starts_on' => 'Ya tienes una renta o una solicitud en esas fechas.']);
});
