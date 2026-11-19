<?php

namespace App\Modules\Inspections\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignInspectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inspector_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'scheduled_for' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['inspector_id' => __('inspector'), 'scheduled_for' => __('date')];
    }
}
