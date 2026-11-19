<?php

namespace App\Modules\Inspections\Http\Requests;

use App\Modules\Inspections\Enums\ChecklistCategory;
use App\Modules\Inspections\Enums\ItemScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChecklistItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ChecklistCategory::class)],
            'text' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::enum(ItemScope::class)],
            'is_critical' => ['boolean'],
            'requires_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['text' => __('text'), 'category' => __('category'), 'scope' => __('where it applies')];
    }
}
