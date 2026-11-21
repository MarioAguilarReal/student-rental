<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;

/**
 * HU-04.01 · Crear propiedad (RN-15) and HU-04.09 · Gestión global.
 */
it('creates a draft property for the owner', function () {
    $owner = userWithRole(Role::Owner);

    $response = $this->actingAs($owner)->post(route('properties.store'), [
        'title' => 'Casa cerca del campus',
        'property_type' => 'house',
        'management_mode' => 'self_managed',
    ]);

    $property = Property::query()->sole();
    $response->assertRedirect(route('properties.edit', $property));

    expect($property->status)->toBe(PropertyStatus::Draft)
        ->and($property->owner_id)->toBe($owner->id)
        ->and($property->created_by)->toBe($owner->id);
});

it('lets an administrator create a property on behalf of an owner (RN-15)', function () {
    $admin = userWithRole(Role::Admin);
    $owner = userWithRole(Role::Owner, ['email' => 'duena@example.com']);

    $this->actingAs($admin)->post(route('properties.store'), [
        'title' => 'Departamento administrado',
        'property_type' => 'apartment',
        'management_mode' => 'managed',
        'owner_email' => 'duena@example.com',
    ])->assertSessionHasNoErrors();

    $property = Property::query()->sole();
    expect($property->owner_id)->toBe($owner->id)->and($property->created_by)->toBe($admin->id);
});

it('shows only owners to administrators creating a property', function () {
    $owner = userWithRole(Role::Owner, ['first_name' => 'Ana', 'last_name' => 'Dueña', 'email' => 'duena@example.com']);
    userWithRole(Role::Client);

    $this->actingAs(userWithRole(Role::Admin))->get(route('properties.create'))
        ->assertInertia(fn ($page) => $page
            ->where('canCreateOnBehalf', true)
            ->has('owners', 1)
            ->where('owners.0.value', $owner->email)
            ->where('owners.0.label', 'Ana Dueña (duena@example.com)'));

    $this->actingAs($owner)->get(route('properties.create'))
        ->assertInertia(fn ($page) => $page->where('canCreateOnBehalf', false)->has('owners', 0));
});

it('only creates properties on behalf of users who are owners', function () {
    userWithRole(Role::Client, ['email' => 'cliente@example.com']);

    $this->actingAs(userWithRole(Role::Admin))->post(route('properties.store'), [
        'title' => 'X',
        'property_type' => 'house',
        'management_mode' => 'managed',
        'owner_email' => 'cliente@example.com',
    ])->assertSessionHasErrors('owner_email');
});

it('forbids clients from creating properties', function () {
    $this->actingAs(userWithRole(Role::Client))->get(route('properties.create'))->assertForbidden();
});

it('lists only my own properties', function () {
    $owner = userWithRole(Role::Owner);
    Property::factory()->create(['owner_id' => $owner->id]);
    Property::factory()->create();

    $this->actingAs($owner)->get(route('properties.index'))
        ->assertInertia(fn ($page) => $page->component('properties/index')->where('scope', 'mine')->has('properties.data', 1));
});

it('lets administrators list and filter every property', function () {
    Property::factory()->create(['management_mode' => 'managed']);
    Property::factory()->create();

    $admin = userWithRole(Role::Admin);
    $this->actingAs($admin)->get(route('properties.admin.index'))->assertInertia(fn ($page) => $page->has('properties.data', 2));
    $this->actingAs($admin)->get(route('properties.admin.index', ['mode' => 'managed']))->assertInertia(fn ($page) => $page->has('properties.data', 1));
    $this->actingAs(userWithRole(Role::Owner))->get(route('properties.admin.index'))->assertForbidden();
});

it('does not let an owner open someone else\'s property', function () {
    $this->actingAs(userWithRole(Role::Owner))
        ->get(route('properties.edit', Property::factory()->create()))
        ->assertForbidden();
});

it('lets administrators operate managed properties but only view self-managed ones (HU-04.09)', function () {
    $admin = userWithRole(Role::Admin);
    $managed = Property::factory()->create(['management_mode' => 'managed']);
    $selfManaged = Property::factory()->create();
    $data = ['title' => 'Nuevo título', 'property_type' => 'house', 'management_mode' => 'managed'];

    $this->actingAs($admin)->put(route('properties.update', $managed), $data)->assertSessionHasNoErrors();
    $this->actingAs($admin)->get(route('properties.edit', $selfManaged))->assertOk()->assertInertia(fn ($page) => $page->where('can.update', false));
    $this->actingAs($admin)->put(route('properties.update', $selfManaged), $data)->assertForbidden();

    expect($managed->fresh()?->title)->toBe('Nuevo título');
});
