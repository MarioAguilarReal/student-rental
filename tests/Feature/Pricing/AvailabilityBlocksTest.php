<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;

/**
 * HU-05.03 · Bloquear fechas (RN-22, RN-26).
 */
beforeEach(function () {
    $this->owner = userWithRole(Role::Owner);
    $this->property = Property::factory()->create(['owner_id' => $this->owner->id]);
    $this->house = Unit::factory()->create(['property_id' => $this->property->id]);
    $this->room = Unit::factory()->childOf($this->house, UnitType::PrivateRoom)->create();
    $this->otherRoom = Unit::factory()->childOf($this->house, UnitType::PrivateRoom)->create();
});

function block(Unit $unit, string $from, string $until)
{
    return test()->actingAs(test()->owner)->post(route('pricing.blocks.store', test()->property), [
        'unit_id' => $unit->id,
        'starts_on' => now()->modify($from)->toDateString(),
        'ends_on' => now()->modify($until)->toDateString(),
        'reason' => 'maintenance',
    ]);
}

it('blocks dates on a space', function () {
    block($this->room, '+10 days', '+15 days')->assertSessionHasNoErrors();

    expect(AvailabilityBlock::query()->sole()->unit_id)->toBe($this->room->id);
});

it('refuses overlaps within the same family of spaces, but not between siblings (RN-22)', function () {
    block($this->room, '+10 days', '+15 days');

    block($this->house, '+14 days', '+20 days')->assertSessionHasErrors('starts_on');
    block($this->otherRoom, '+12 days', '+14 days')->assertSessionHasNoErrors();
    block($this->room, '+15 days', '+18 days')->assertSessionHasNoErrors(); // the end date is exclusive
});

it('validates the dates', function (string $from, string $until, string $field) {
    block($this->room, $from, $until)->assertSessionHasErrors($field);
})->with([
    'end before start' => ['+10 days', '+5 days', 'ends_on'],
    'same day' => ['+10 days', '+10 days', 'ends_on'],
    'in the past' => ['-3 days', '+2 days', 'starts_on'],
    'more than two years ahead' => ['+700 days', '+800 days', 'starts_on'],
]);

it('removes a block, only through its own property', function () {
    block($this->room, '+10 days', '+15 days');
    $block = AvailabilityBlock::query()->sole();
    $other = Property::factory()->create(['owner_id' => $this->owner->id]);

    $this->actingAs($this->owner)->delete(route('pricing.blocks.destroy', [$other, $block]))->assertNotFound();
    $this->actingAs($this->owner)->delete(route('pricing.blocks.destroy', [$this->property, $block]))->assertSessionHasNoErrors();

    expect(AvailabilityBlock::query()->count())->toBe(0);
});

it('does not block spaces of another property', function () {
    $foreign = Unit::factory()->create();

    $this->actingAs($this->owner)->post(route('pricing.blocks.store', $this->property), [
        'unit_id' => $foreign->id,
        'starts_on' => now()->addDays(3)->toDateString(),
        'ends_on' => now()->addDays(5)->toDateString(),
        'reason' => 'owner_use',
    ])->assertNotFound();
});
