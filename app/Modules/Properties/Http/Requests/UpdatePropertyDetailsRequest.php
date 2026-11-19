<?php

namespace App\Modules\Properties\Http\Requests;

use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyDetailsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'property_type' => ['required', Rule::enum(PropertyType::class)],
            'management_mode' => ['required', Rule::enum(ManagementMode::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => __('title'), 'description' => __('description'), 'property_type' => __('property type'), 'management_mode' => __('management mode')];
    }
}
