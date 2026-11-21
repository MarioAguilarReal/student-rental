<?php

namespace App\Modules\Payments\Http\Requests;

use App\Modules\Properties\Enums\ManagementMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HU-03.04: a new rate, as a percentage with up to two decimals.
 */
class CommissionRuleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'management_mode' => ['required', Rule::enum(ManagementMode::class)],
            'percentage' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'valid_from' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'management_mode' => __('management mode'),
            'percentage' => __('commission'),
            'valid_from' => __('start date'),
        ];
    }

    public function basisPoints(): int
    {
        return (int) round($this->float('percentage') * 100);
    }
}
