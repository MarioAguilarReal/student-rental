<?php

use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Identity\Enums\Role;
use Database\Seeders\CatalogDefaultsSeeder;

/**
 * HU-03.02 · Amenidades y reglas de la casa.
 */
it('lets the super admin add and deactivate amenities with a scope', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);

    $this->actingAs($superAdmin)
        ->post(route('catalog.amenities.store'), ['name' => 'Baño privado', 'category' => 'bathroom', 'scope' => 'unit', 'is_active' => true])
        ->assertSessionHasNoErrors();

    $amenity = Amenity::query()->sole();
    expect($amenity->scope->value)->toBe('unit');

    $this->actingAs($superAdmin)
        ->put(route('catalog.amenities.update', $amenity), ['name' => 'Baño privado', 'category' => 'bathroom', 'scope' => 'unit', 'is_active' => false]);

    expect($amenity->fresh()?->is_active)->toBeFalse();
});

it('manages house rules', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('catalog.house-rules.store'), ['name' => 'Mascotas', 'description' => 'Se permiten animales'])
        ->assertSessionHasNoErrors();

    expect(HouseRule::query()->sole()->is_active)->toBeTrue();
});

it('seeds a default catalog idempotently', function () {
    $this->seed(CatalogDefaultsSeeder::class);
    $this->seed(CatalogDefaultsSeeder::class);

    expect(Amenity::query()->count())->toBe(20)
        ->and(HouseRule::query()->count())->toBe(5);
});

it('keeps the catalog away from owners and admins', function (Role $role) {
    $this->actingAs(userWithRole($role))->get(route('catalog.amenities.index'))->assertForbidden();
})->with([Role::Owner, Role::Admin]);
