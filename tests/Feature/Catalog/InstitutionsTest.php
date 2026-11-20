<?php

use App\Modules\Catalog\Models\Campus;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Identity\Enums\Role;

/**
 * HU-03.01 · Instituciones y campus.
 */
it('creates an institution with normalized email domains', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('catalog.institutions.store'), [
            'name' => 'Universidad Autónoma de Nayarit',
            'acronym' => 'UAN',
            'email_domains' => '@UAN.edu.mx, uan.mx',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(Institution::query()->sole()->email_domains)->toBe(['uan.edu.mx', 'uan.mx']);
});

it('does not allow the same domain in two institutions', function () {
    Institution::factory()->create(['name' => 'UAN', 'email_domains' => ['uan.edu.mx']]);

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('catalog.institutions.store'), [
            'name' => 'Otra',
            'email_domains' => ['uan.edu.mx'],
        ])
        ->assertSessionHasErrors('email_domains');

    expect(Institution::query()->count())->toBe(1);
});

it('requires at least one valid domain', function (mixed $domains) {
    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('catalog.institutions.store'), ['name' => 'X', 'email_domains' => $domains])
        ->assertSessionHasErrors();
})->with(['empty' => [[]], 'not a domain' => [['no es dominio']]]);

it('adds, updates and deletes campuses with coordinates', function () {
    $institution = Institution::factory()->create();
    $superAdmin = userWithRole(Role::SuperAdmin);

    $this->actingAs($superAdmin)
        ->post(route('catalog.institutions.campuses.store', $institution), [
            'name' => 'Campus Tepic',
            'latitude' => 21.4920,
            'longitude' => -104.8942,
        ])
        ->assertSessionHasNoErrors();

    $campus = $institution->campuses()->sole();
    expect((float) $campus->latitude)->toBe(21.4920);

    $this->actingAs($superAdmin)
        ->put(route('catalog.institutions.campuses.update', [$institution, $campus]), [
            'name' => 'Campus Norte',
            'latitude' => 21.51,
            'longitude' => -104.89,
        ])
        ->assertSessionHasNoErrors();
    expect($campus->fresh()?->name)->toBe('Campus Norte');

    $this->actingAs($superAdmin)->delete(route('catalog.institutions.campuses.destroy', [$institution, $campus]));
    expect(Campus::query()->count())->toBe(0);
});

it('requires coordinates for a campus', function () {
    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('catalog.institutions.campuses.store', Institution::factory()->create()), ['name' => 'Sin ubicación'])
        ->assertSessionHasErrors(['latitude', 'longitude']);
});

it('only lets a campus be changed through its own institution', function () {
    $campus = Campus::factory()->create();

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->delete(route('catalog.institutions.campuses.destroy', [Institution::factory()->create(), $campus]))
        ->assertNotFound();
});

it('keeps the catalog away from admins and clients', function (Role $role) {
    $this->actingAs(userWithRole($role))->get(route('catalog.institutions.index'))->assertForbidden();
})->with([Role::Admin, Role::Client]);
