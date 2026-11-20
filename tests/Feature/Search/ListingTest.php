<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;

/**
 * HU-07.02 · Detalle de la propiedad.
 */
it('shows a listed property to anyone, without the exact address', function () {
    $property = listedProperty(['street' => 'Calle Secreta', 'exterior_number' => '123']);

    $response = $this->get(route('stays.show', $property))->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['property']['title'])->toBe($property->title)
        ->and($props['preview'])->toBeFalse()
        ->and(json_encode($props))->not->toContain('Calle Secreta')
        ->and($props['units'])->toHaveCount(1);
});

it('hides unpublished properties from visitors but lets the owner preview them', function () {
    $owner = userWithRole(Role::Owner);
    $draft = Property::factory()->withAddress()->create(['owner_id' => $owner->id]);

    $this->get(route('stays.show', $draft))->assertNotFound();
    $this->actingAs(userWithRole(Role::Client))->get(route('stays.show', $draft))->assertNotFound();
    $this->actingAs($owner)->get(route('stays.show', $draft))->assertInertia(fn ($page) => $page->where('preview', true));
});

it('quotes a stay: total, deposit and the end date by RN-42', function () {
    $property = listedProperty();
    $unit = $property->units()->firstOrFail();
    UnitDeposit::create(['unit_id' => $unit->id, 'amount_minor' => 450_000]);
    $start = now()->addMonth()->startOfMonth();

    $this->get(route('stays.show', [$property, 'unit' => $unit->id, 'period' => 'monthly', 'start' => $start->toDateString(), 'periods' => 5]))
        ->assertInertia(fn ($page) => $page
            ->where('quote.total_minor', 2_250_000)
            ->where('quote.deposit_minor', 450_000)
            ->where('quote.end', $start->addMonthsNoOverflow(5)->toDateString())
            ->where('quote.available', true)
            ->where('quote.errors', [])
            ->has('quote.charges', 5)
            ->where('quote.charges.0.amount_minor', 900_000)
            ->where('quote.charges.0.due_on', null)
            ->where('quote.charges.1.due_on', $start->addMonthNoOverflow()->toDateString()));
});

it('explains why a stay cannot be quoted', function (array $query, string $message) {
    $property = listedProperty();
    $unit = $property->units()->firstOrFail();
    AvailabilityBlock::create([
        'unit_id' => $unit->id, 'starts_on' => now()->addDays(30)->toDateString(), 'ends_on' => now()->addDays(40)->toDateString(),
        'reason' => 'maintenance', 'created_by' => $property->owner_id,
    ]);

    $this->get(route('stays.show', [$property, 'unit' => $unit->id, ...$query]))
        ->assertInertia(fn ($page) => $page->where('quote.available', false)->where('quote.errors.0', $message));
})->with([
    'today' => [fn () => ['period' => 'monthly', 'start' => now()->toDateString(), 'periods' => 1], 'La fecha de entrada debe ser a partir de mañana y dentro de los próximos 12 meses.'],
    'too long' => [fn () => ['period' => 'monthly', 'start' => now()->addDays(2)->toDateString(), 'periods' => 13], 'Para este espacio elige entre 1 y 12 periodos.'],
    'blocked' => [fn () => ['period' => 'monthly', 'start' => now()->addDays(20)->toDateString(), 'periods' => 1], 'El espacio no está disponible en esas fechas.'],
    'no weekly plan' => [fn () => ['period' => 'weekly', 'start' => now()->addDays(2)->toDateString(), 'periods' => 1], 'Este espacio no se ofrece con ese periodo.'],
]);

it('lists the unavailable ranges of each space', function () {
    $property = listedProperty();
    $unit = $property->units()->firstOrFail();
    AvailabilityBlock::create([
        'unit_id' => $unit->id, 'starts_on' => now()->addDays(3)->toDateString(), 'ends_on' => now()->addDays(6)->toDateString(),
        'reason' => 'maintenance', 'created_by' => $property->owner_id,
    ]);

    $this->get(route('stays.show', $property))->assertInertia(fn ($page) => $page->has('units.0.unavailable', 1));
});

it('stops listing a property once it is paused', function () {
    $property = listedProperty();
    $property->update(['status' => PropertyStatus::Paused]);

    $this->get(route('stays.show', $property))->assertNotFound();
});
