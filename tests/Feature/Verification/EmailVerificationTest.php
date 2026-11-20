<?php

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Identity\Enums\Role;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use Illuminate\Support\Facades\URL;

/**
 * HU-02.01 · Verificar correo y asociar institución (RN-02).
 */
function verifyEmailOf(User $user): void
{
    test()->actingAs($user)->get(URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    ));
}

it('moves the profiles to email verified once the link is clicked', function () {
    $user = userWithRole(Role::Client, ['email_verified_at' => null]);

    verifyEmailOf($user);

    expect($user->fresh()?->verificationProfile(VerificationKind::Client)?->status)->toBe(VerificationStatus::EmailVerified);
});

it('links the client to the institution that owns the email domain, subdomains included', function (string $email) {
    $institution = Institution::factory()->create(['email_domains' => ['uan.edu.mx']]);
    $user = userWithRole(Role::Client, ['email' => $email, 'email_verified_at' => null]);

    verifyEmailOf($user);

    expect($user->fresh()?->verificationProfile(VerificationKind::Client)?->institution_id)->toBe($institution->id);
})->with(['ana@uan.edu.mx', 'ana@alumnos.uan.edu.mx']);

it('leaves the institution empty when no domain matches, so the client can pick it', function () {
    Institution::factory()->create(['email_domains' => ['uan.edu.mx']]);
    $user = userWithRole(Role::Client, ['email' => 'erasmus@uni-heidelberg.de', 'email_verified_at' => null]);

    verifyEmailOf($user);

    expect($user->fresh()?->verificationProfile(VerificationKind::Client)?->institution_id)->toBeNull();
});

it('ignores inactive institutions', function () {
    Institution::factory()->inactive()->create(['email_domains' => ['uan.edu.mx']]);
    $user = userWithRole(Role::Client, ['email' => 'ana@uan.edu.mx', 'email_verified_at' => null]);

    verifyEmailOf($user);

    expect($user->fresh()?->verificationProfile(VerificationKind::Client)?->institution_id)->toBeNull();
});

it('does not let users with an unverified email reach the verification page', function () {
    $this->actingAs(userWithRole(Role::Client, ['email_verified_at' => null]))
        ->get(route('verifications.show'))
        ->assertRedirect(route('verification.notice'));
});
