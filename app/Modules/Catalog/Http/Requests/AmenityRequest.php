<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Enums\AmenityCategory;
use App\Modules\Catalog\Enums\AmenityScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AmenityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::enum(AmenityCategory::class)],
            'scope' => ['required', Rule::enum(AmenityScope::class)],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => __('name'), 'category' => __('category'), 'scope' => __('where it applies')];
    }
}
