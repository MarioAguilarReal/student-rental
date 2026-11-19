<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Modules\Identity\Actions\RegisterUser;
use App\Modules\Identity\Enums\Role;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'account_type' => ['required', Rule::in(array_map(fn (Role $role): string => $role->value, Role::selfAssignable()))],
        ], attributes: [
            'birth_date' => __('birth date'),
            'account_type' => __('account type'),
            'first_name' => __('first name'),
            'last_name' => __('last name'),
        ])->validate();

        return app(RegisterUser::class)->handle(
            $input['first_name'],
            $input['last_name'],
            $input['email'],
            $input['password'],
            CarbonImmutable::parse($input['birth_date']),
            Role::from($input['account_type']),
        );
    }
}
