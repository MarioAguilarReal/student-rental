<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-01.01: visitors sign up as client or owner. RN-05: birth date is captured,
 * the policy for minors is still pending (D-21), so it does not restrict sign-up.
 */
class RegisterUser
{
    public function __construct(private AddAccountProfile $addAccountProfile) {}

    public function handle(string $firstName, string $lastName, string $email, string $password, CarbonImmutable $birthDate, Role $role): User
    {
        return DB::transaction(function () use ($firstName, $lastName, $email, $password, $birthDate, $role) {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password,
                'birth_date' => $birthDate,
            ]);

            $this->addAccountProfile->handle($user, $role);

            return $user;
        });
    }
}
