<?php

use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;

/**
 * HU-04.04 · Amenidades, reglas y convivencia (RN-30, RN-31, RN-33).
 */
beforeEach(function () {
    $this->owner = userWithRole(Role::Owner);
    $this->property = Property::factory()->create(['owner_id' => $this->owner->id]);
});

function featuresData(array $overrides = []): array
{
    return ['amenity_ids' => [], 'house_rules' => [], 'gender_policy' => 'mixed', 'allowed_communities' => [], ...$overrides];
}

it('saves shared amenities, house rules and coexistence rules', function () {
    $wifi = Amenity::factory()->scope(AmenityScope::Property)->create();
    $pets = HouseRule::factory()->create();

    $this->actingAs($this->owner)->put(route('properties.features.update', $this->property), featuresData([
        'amenity_ids' => [$wifi->id],
        'house_rules' => [['id' => $pets->id, 'is_allowed' => false, 'note' => 'Solo peces']],
        'gender_policy' => 'women_only',
        'allowed_communities' => ['student', 'exchange_student'],
    ]))->assertSessionHasNoErrors();

    $this->property->refresh();
    expect($this->property->amenities->pluck('id')->all())->toBe([$wifi->id])
        ->and($this->property->gender_policy)->toBe(GenderPolicy::WomenOnly)
        ->and($this->property->allowed_communities)->toBe(['student', 'exchange_student'])
        ->and((bool) $this->property->houseRules->first()?->getRelationValue('pivot')?->getAttribute('is_allowed'))->toBeFalse();
});

it('rejects unit-only amenities for shared areas', function () {
    $privateBath = Amenity::factory()->scope(AmenityScope::Unit)->create();

    $this->actingAs($this->owner)
        ->put(route('properties.features.update', $this->property), featuresData(['amenity_ids' => [$privateBath->id]]))
        ->assertSessionHasErrors('amenity_ids');
});

it('lets a unit tighten but never loosen the gender policy (RN-30)', function (string $property, ?string $unit, bool $valid) {
    $this->property->update(['gender_policy' => $property]);

    $response = $this->actingAs($this->owner)->post(route('properties.units.store', $this->property), [
        'type' => 'entire_place', 'name' => 'Casa', 'capacity' => 3, 'is_rentable' => true, 'gender_policy' => $unit,
    ]);

    $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('gender_policy');
})->with([
    'mixed → women only' => ['mixed', 'women_only', true],
    'women only → inherit' => ['women_only', null, true],
    'women only → mixed' => ['women_only', 'mixed', false],
    'women only → men only' => ['women_only', 'men_only', false],
]);

it('inherits the nearest restriction', function () {
    $this->property->update(['gender_policy' => 'mixed']);
    $room = Unit::factory()->create(['property_id' => $this->property->id, 'type' => UnitType::SharedRoom, 'gender_policy' => GenderPolicy::MenOnly]);
    $bed = Unit::factory()->childOf($room, UnitType::Bed)->create();

    expect($bed->effectiveGenderPolicy())->toBe(GenderPolicy::MenOnly);
});

it('does not let the property loosen rules below what its units require', function () {
    $this->property->update(['gender_policy' => 'women_only']);
    Unit::factory()->create(['property_id' => $this->property->id, 'gender_policy' => GenderPolicy::WomenOnly]);

    $this->actingAs($this->owner)
        ->put(route('properties.features.update', $this->property), featuresData(['gender_policy' => 'men_only']))
        ->assertSessionHasErrors('gender_policy');

    expect($this->property->fresh()?->gender_policy)->toBe(GenderPolicy::WomenOnly);
});

it('lets a unit only restrict the communities it inherits (RN-31)', function (array $property, ?array $unit, bool $valid) {
    $this->property->update(['allowed_communities' => $property]);

    $response = $this->actingAs($this->owner)->post(route('properties.units.store', $this->property), [
        'type' => 'entire_place', 'name' => 'Casa', 'capacity' => 3, 'is_rentable' => true,
        'inherit_communities' => $unit === null, 'allowed_communities' => $unit ?? [],
    ]);

    $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('allowed_communities');
})->with([
    'everyone → students' => [[], ['student'], true],
    'students → inherit' => [['student'], null, true],
    'students → faculty' => [['student'], ['faculty'], false],
    'students → everyone' => [['student'], [], false],
]);
