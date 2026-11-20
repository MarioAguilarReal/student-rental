<?php

namespace App\Modules\Rentals\Http\Requests;

use App\Modules\Identity\Enums\CohabitationGender;
use App\Modules\Pricing\Enums\RatePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestRentalRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer'],
            'period' => ['required', Rule::enum(RatePeriod::class)],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'periods' => ['required', 'integer', 'min:1', 'max:104'],
            'accept_rules' => ['boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', Rule::enum(CohabitationGender::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'unit_id' => __('space'), 'period' => __('period'), 'starts_on' => __('move-in date'),
            'periods' => __('periods'), 'message' => __('message'), 'gender' => __('gender'),
        ];
    }
}
