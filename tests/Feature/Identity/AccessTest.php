<?php

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use Illuminate\Support\Facades\Password;

/**
 * HU-01.02 · Inicio de sesión y recuperación. (The 5-attempt lockout is covered by the
 * starter kit's AuthenticationTest::test_users_are_rate_limited.)
 */
it('rejects the sign-in of a suspended account with a clear message', function () {
    $user = userWithRole(Role::Client);
    $user->forceFill(['suspended_at' => now(), 'suspension_reason' => 'Spam'])->save();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Tu cuenta ha sido suspendida.']);

    $this->assertGuest();
});

it('signs out a user that gets suspended while signed in', function () {
    $user = userWithRole(Role::Client);
    $this->actingAs($user);
    $user->forceFill(['suspended_at' => now()])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('expires password reset links after one hour', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->travel(61)->minutes();

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('email');
});

it('uses each password reset link only once', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $reset = fn () => $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $reset()->assertSessionHasNoErrors();
    $reset()->assertSessionHasErrors('email');
});
