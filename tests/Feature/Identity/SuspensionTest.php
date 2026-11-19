<?php

use App\Modules\Identity\Enums\Role;
use Illuminate\Support\Facades\DB;

/**
 * HU-01.04 · Suspensión de usuarios.
 */
it('suspends a user with a reason and closes their sessions', function () {
    $admin = userWithRole(Role::Admin);
    $client = userWithRole(Role::Client);
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $client->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($admin)
        ->post(route('users.suspension.store', $client), ['reason' => 'Publicó datos falsos'])
        ->assertSessionHasNoErrors();

    $client->refresh();

    expect($client->isSuspended())->toBeTrue()
        ->and($client->suspension_reason)->toBe('Publicó datos falsos');
    $this->assertDatabaseMissing('sessions', ['user_id' => $client->id]);
    $this->assertDatabaseHas('activity_log', ['event' => 'suspended', 'subject_id' => $client->id]);
});

it('requires a reason', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->post(route('users.suspension.store', userWithRole(Role::Client)), ['reason' => ''])
        ->assertSessionHasErrors('reason');
});

it('does not let anyone suspend themselves', function () {
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)
        ->post(route('users.suspension.store', $admin), ['reason' => 'Prueba de auto suspensión'])
        ->assertSessionHasErrors('reason');

    expect($admin->fresh()?->isSuspended())->toBeFalse();
});

it('only lets a super admin suspend administrators', function () {
    $target = userWithRole(Role::Admin);

    $this->actingAs(userWithRole(Role::Admin))
        ->post(route('users.suspension.store', $target), ['reason' => 'Abuso de privilegios'])
        ->assertSessionHasErrors('reason');

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('users.suspension.store', $target), ['reason' => 'Abuso de privilegios'])
        ->assertSessionHasNoErrors();

    expect($target->fresh()?->isSuspended())->toBeTrue();
});

it('reactivates a suspended user', function () {
    $client = userWithRole(Role::Client);
    $client->forceFill(['suspended_at' => now(), 'suspension_reason' => 'Error'])->save();

    $this->actingAs(userWithRole(Role::Admin))
        ->delete(route('users.suspension.destroy', $client))
        ->assertSessionHasNoErrors();

    expect($client->fresh()?->isSuspended())->toBeFalse();
});

it('forbids clients from suspending', function () {
    $this->actingAs(userWithRole(Role::Client))
        ->post(route('users.suspension.store', userWithRole(Role::Client)), ['reason' => 'No autorizado'])
        ->assertForbidden();
});
