<?php

use App\Modules\Identity\Enums\Role;

/**
 * HU-01.03 · Asignación de roles internos.
 */
it('lets a super admin grant internal roles without touching client/owner roles', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);
    $user = userWithRole(Role::Client);

    $this->actingAs($superAdmin)
        ->put(route('users.roles.update', $user), ['roles' => ['inspector']])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->hasRole(Role::Inspector->value))->toBeTrue()
        ->and($user->hasRole(Role::Client->value))->toBeTrue();

    $this->assertDatabaseHas('activity_log', ['event' => 'roles_changed', 'subject_id' => $user->id, 'causer_id' => $superAdmin->id]);
});

it('revokes internal roles that are not sent', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);
    $user = userWithRole(Role::Inspector);

    $this->actingAs($superAdmin)->put(route('users.roles.update', $user), ['roles' => []]);

    expect($user->fresh()?->hasRole(Role::Inspector->value))->toBeFalse();
});

it('does not let a super admin remove their own super admin role', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);

    $this->actingAs($superAdmin)
        ->put(route('users.roles.update', $superAdmin), ['roles' => ['admin']])
        ->assertSessionHasErrors('roles');

    expect($superAdmin->fresh()?->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

it('forbids admins from assigning roles', function () {
    $admin = userWithRole(Role::Admin);
    $user = userWithRole(Role::Client);

    $this->actingAs($admin)->put(route('users.roles.update', $user), ['roles' => ['admin']])->assertForbidden();
});

it('shows the user directory only to staff', function (Role $role, int $status) {
    $this->actingAs(userWithRole($role))->get(route('users.index'))->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'admin' => [Role::Admin, 200],
    'inspector' => [Role::Inspector, 403],
    'client' => [Role::Client, 403],
]);

it('searches users by full name across first and last name', function () {
    userWithRole(Role::Client, ['first_name' => 'Lucía', 'last_name' => 'Méndez Soto', 'email' => 'l@example.com']);

    $this->actingAs(userWithRole(Role::Admin))
        ->get(route('users.index', ['search' => 'lucía méndez']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1)->where('users.data.0.name', 'Lucía Méndez Soto'));
});

it('searches users by name or email', function () {
    userWithRole(Role::Client, ['first_name' => 'Lucía', 'last_name' => 'Méndez Soto', 'email' => 'lucia@example.com']);
    userWithRole(Role::Client, ['first_name' => 'Pedro', 'last_name' => 'Ruiz', 'email' => 'pedro@example.com']);

    $this->actingAs(userWithRole(Role::Admin))
        ->get(route('users.index', ['search' => 'lucia']))
        ->assertInertia(fn ($page) => $page
            ->component('identity/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'lucia@example.com'));
});
