<?php

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;

/**
 * HU-01.01 · Registro.
 */
function registrationData(array $overrides = []): array
{
    return [
        'first_name' => 'Ana María',
        'last_name' => 'Torres Gil',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'birth_date' => '2003-02-10',
        'account_type' => 'client',
        ...$overrides,
    ];
}

it('registers a client with an unverified client profile', function () {
    $this->post(route('register.store'), registrationData())->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'ana@example.com')->sole();

    expect($user->hasRole(Role::Client->value))->toBeTrue()
        ->and($user->first_name)->toBe('Ana María')
        ->and($user->last_name)->toBe('Torres Gil')
        ->and($user->name)->toBe('Ana María Torres Gil')
        ->and($user->birth_date?->toDateString())->toBe('2003-02-10')
        ->and($user->verificationProfile(VerificationKind::Client)?->status)->toBe(VerificationStatus::Unverified)
        ->and($user->verificationProfile(VerificationKind::Owner))->toBeNull();
});

it('registers an owner with an owner profile', function () {
    $this->post(route('register.store'), registrationData(['account_type' => 'owner']));

    $user = User::query()->where('email', 'ana@example.com')->sole();

    expect($user->hasRole(Role::Owner->value))->toBeTrue()
        ->and($user->hasRole(Role::Client->value))->toBeFalse()
        ->and($user->verificationProfile(VerificationKind::Owner))->not->toBeNull();
});

it('requires a birth date and a valid account type', function (array $data, string $field) {
    $this->post(route('register.store'), registrationData($data))->assertSessionHasErrors($field);

    $this->assertGuest();
})->with([
    'missing birth date' => [['birth_date' => ''], 'birth_date'],
    'birth date in the future' => [['birth_date' => now()->addDay()->toDateString()], 'birth_date'],
    'internal role is not self-assignable' => [['account_type' => 'admin'], 'account_type'],
    'missing last name (D-48)' => [['last_name' => ''], 'last_name'],
]);

it('does not restrict sign-up by age while the minors policy is pending (D-21)', function () {
    $this->post(route('register.store'), registrationData(['birth_date' => now()->subYears(17)->toDateString()]));

    $this->assertAuthenticated();
});

it('lets an existing client add the owner profile', function () {
    $user = userWithRole(Role::Client);

    $this->actingAs($user)
        ->post(route('account-profiles.store'), ['role' => 'owner'])
        ->assertRedirect(route('verifications.show'));

    $user->refresh();

    expect($user->hasRole(Role::Owner->value))->toBeTrue()
        ->and($user->verificationProfiles()->count())->toBe(2);
});

it('cannot add an internal role as an account profile', function () {
    $user = userWithRole(Role::Client);

    $this->actingAs($user)
        ->post(route('account-profiles.store'), ['role' => 'admin'])
        ->assertSessionHasErrors('role');

    expect($user->fresh()?->hasRole(Role::Admin->value))->toBeFalse();
});
