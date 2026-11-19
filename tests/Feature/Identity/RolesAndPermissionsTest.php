<?php

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates every role and permission', function () {
    expect(RoleModel::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect(Role::cases())->map->value->sort()->values()->all());
});

it('is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleModel::query()->count())->toBe(count(Role::cases()));
});

it('gives the super admin every permission', function () {
    $user = User::factory()->create()->assignRole(Role::SuperAdmin->value);

    foreach (Permission::cases() as $permission) {
        expect($user->can($permission->value))->toBeTrue();
    }
});

it('applies the role matrix from docs/spec/02-roles-permisos.md', function (Role $role, Permission $permission, bool $expected) {
    $user = User::factory()->create()->assignRole($role->value);

    expect($user->can($permission->value))->toBe($expected);
})->with([
    'client requests rentals' => [Role::Client, Permission::RentalsRequest, true],
    'client cannot create properties' => [Role::Client, Permission::PropertiesCreate, false],
    'owner creates properties' => [Role::Owner, Permission::PropertiesCreate, true],
    'owner cannot assign inspections' => [Role::Owner, Permission::InspectionsAssign, false],
    'owner cannot cancel rentals (RN-66)' => [Role::Owner, Permission::RentalsCancel, false],
    'inspector performs inspections' => [Role::Inspector, Permission::InspectionsPerform, true],
    'inspector cannot decide rentals' => [Role::Inspector, Permission::RentalsDecide, false],
    'admin reviews verification documents' => [Role::Admin, Permission::VerificationDocumentsReview, true],
    'admin cannot manage commission rules' => [Role::Admin, Permission::CommissionRulesManage, false],
    'admin cannot perform inspections' => [Role::Admin, Permission::InspectionsPerform, false],
]);
