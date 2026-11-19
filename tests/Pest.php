<?php

use App\Models\User;
use App\Modules\Identity\Actions\AddAccountProfile;
use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Actions\OpenInspection;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\CommissionRulesSeeder;
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
    ->beforeEach(fn () => $this->seed([RolesAndPermissionsSeeder::class, CommissionRulesSeeder::class, ChecklistSeeder::class]))
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
        'Search',
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

/**
 * A property with an address, two rentable spaces (house + room) and an open inspection.
 */
function propertyAwaitingInspection(): Inspection
{
    $owner = verifiedOwner();
    $property = Property::factory()->withAddress()
        ->status(PropertyStatus::PendingInspection)
        ->create(['owner_id' => $owner->id]);
    $house = Unit::factory()->create(['property_id' => $property->id]);
    Unit::factory()->childOf($house, UnitType::PrivateRoom)->create();

    return app(OpenInspection::class)->handle($property, InspectionType::Initial);
}

/**
 * A published, inspected property near (32.53, -117.02) with one rentable space and a monthly plan.
 *
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $unit
 */
function listedProperty(array $attributes = [], array $unit = [], int $monthlyMinor = 450_000): Property
{
    $property = Property::factory()->inspected()->create([
        'status' => PropertyStatus::Published,
        'published_at' => now(),
        'latitude' => 32.5300,
        'longitude' => -117.0200,
        ...$attributes,
    ]);
    $space = Unit::factory()->create(['property_id' => $property->id, ...$unit]);
    RatePlan::create([
        'unit_id' => $space->id,
        'period' => RatePeriod::Monthly,
        'price_minor' => $monthlyMinor,
        'min_periods' => 1,
        'max_periods' => 12,
    ]);

    return $property;
}
