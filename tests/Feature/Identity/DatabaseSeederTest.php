<?php

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentUsersSeeder;
use Spatie\Permission\Models\Role as RoleModel;

// Mirrors `php artisan migrate:fresh --seed`: the full seeder on an empty database.
it('seeds roles and one development user per role on an empty database', function () {
    $this->seed(DatabaseSeeder::class);

    expect(RoleModel::query()->count())->toBe(count(Role::cases()));

    foreach (DevelopmentUsersSeeder::USERS as $role => $attributes) {
        $user = User::query()->where('email', $attributes['email'])->sole();

        expect($user->hasRole($role))->toBeTrue()
            ->and($user->hasVerifiedEmail())->toBeTrue();
    }
});

it('can run again without duplicating anything', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(count(DevelopmentUsersSeeder::USERS))
        ->and(RoleModel::query()->count())->toBe(count(Role::cases()));
});
