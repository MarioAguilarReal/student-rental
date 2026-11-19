<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Actions\AddAccountProfile;
use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Seeder;

/**
 * One verified user per role for local development. Password: "password".
 * Never runs in production (see DatabaseSeeder).
 */
class DevelopmentUsersSeeder extends Seeder
{
    /**
     * @var array<string, array{first_name: string, last_name: string, email: string}>
     */
    public const USERS = [
        'super_admin' => ['first_name' => 'Sofía', 'last_name' => 'Superadmin', 'email' => 'superadmin@example.test'],
        'admin' => ['first_name' => 'Andrés', 'last_name' => 'Administrador', 'email' => 'admin@example.test'],
        'inspector' => ['first_name' => 'Isabel', 'last_name' => 'Inspectora', 'email' => 'inspector@example.test'],
        'owner' => ['first_name' => 'Pablo', 'last_name' => 'Propietario', 'email' => 'owner@example.test'],
        'client' => ['first_name' => 'Carla', 'last_name' => 'Cliente', 'email' => 'client@example.test'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $role => $attributes) {
            $user = User::query()->where('email', $attributes['email'])->first()
                ?? User::factory()->create($attributes);

            $roleEnum = Role::from($role);

            // Client and owner go through the same path as sign-up, so they get their verification profile.
            in_array($roleEnum, Role::selfAssignable(), true)
                ? app(AddAccountProfile::class)->handle($user, $roleEnum)
                : $user->syncRoles([$roleEnum->value]);
        }
    }
}
