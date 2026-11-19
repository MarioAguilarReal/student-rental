<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => [Rule::enum(Role::class)],
        ];
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return array_values(array_map(fn (string $role): Role => Role::from($role), $this->array('roles')));
    }
}
