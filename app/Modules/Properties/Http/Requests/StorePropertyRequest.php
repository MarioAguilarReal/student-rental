<?php

namespace App\Modules\Properties\Http\Requests;

use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'property_type' => ['required', Rule::enum(PropertyType::class)],
            'management_mode' => ['required', Rule::enum(ManagementMode::class)],
            'owner_email' => ['nullable', 'email', Rule::exists('users', 'email')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => __('title'), 'property_type' => __('property type'), 'management_mode' => __('management mode'), 'owner_email' => __('owner email')];
    }
}
