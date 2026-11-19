<?php

namespace App\Modules\Inspections\Http\Requests;

use App\Modules\Inspections\Enums\InspectionResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteInspectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(InspectionResult::class)],
            'observations' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['result' => __('result'), 'observations' => __('observations')];
    }
}
