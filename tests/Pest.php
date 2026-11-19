<?php

use App\Models\User;
use App\Modules\Identity\Actions\AddAccountProfile;
use App\Modules\Identity\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Closure-based (Pest) feature tests run against PostgreSQL (see phpunit.xml)
| with a fresh database per test. Class-based tests shipped with the starter
| kit configure themselves.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

// Unit tests boot the framework (translator, config) but never touch the database.
pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Business modules
|--------------------------------------------------------------------------
*/

/**
 * @return list<string>
 */
function businessModules(): array
{
    return [
        'Identity',
        'Verification',
        'Catalog',
        'Properties',
        'Pricing',
        'Rentals',
        'Payments',
        'Inspections',
        'Notifications',
        'Audit',
    ];
}

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

/**
 * A user with the given role. Client and owner roles also get their verification
 * profile, exactly as sign-up does.
 *
 * @param  array<string, mixed>  $attributes
 */
function userWithRole(Role $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    if (in_array($role, Role::selfAssignable(), true)) {
        app(AddAccountProfile::class)->handle($user, $role);
    } else {
        $user->assignRole($role->value);
    }

    return $user->fresh() ?? $user;
}

/**
 * An owner whose identity is already verified (RN-06).
 */
function verifiedOwner(array $attributes = []): User
{
    $owner = userWithRole(Role::Owner, $attributes);
    $owner->verificationProfiles()->update(['status' => 'verified', 'reviewed_at' => now()]);

    return $owner->fresh() ?? $owner;
}
