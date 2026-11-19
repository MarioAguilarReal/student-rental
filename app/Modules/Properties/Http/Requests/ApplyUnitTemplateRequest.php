<?php

namespace App\Modules\Properties\Http\Requests;

use App\Modules\Properties\Enums\UnitTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyUnitTemplateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template' => ['required', Rule::enum(UnitTemplate::class)],
            'rooms' => ['required', 'integer', 'min:0', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
