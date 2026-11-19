<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddAccountProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_map(fn (Role $role): string => $role->value, Role::selfAssignable()))],
        ];
    }
}
