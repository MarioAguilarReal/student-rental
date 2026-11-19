<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Seeder;

/**
 * One verified user per role for local development. Password: "password".
 * Never runs in production (see DatabaseSeeder).
 */
class DevelopmentUsersSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, email: string}>
     */
    public const USERS = [
        'super_admin' => ['name' => 'Sofía Superadmin', 'email' => 'superadmin@example.test'],
        'admin' => ['name' => 'Andrés Administrador', 'email' => 'admin@example.test'],
        'inspector' => ['name' => 'Isabel Inspectora', 'email' => 'inspector@example.test'],
        'owner' => ['name' => 'Pablo Propietario', 'email' => 'owner@example.test'],
        'client' => ['name' => 'Carla Cliente', 'email' => 'client@example.test'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $role => $attributes) {
            $user = User::query()->where('email', $attributes['email'])->first()
                ?? User::factory()->create($attributes);

            $user->syncRoles([Role::from($role)->value]);
        }
    }
}
