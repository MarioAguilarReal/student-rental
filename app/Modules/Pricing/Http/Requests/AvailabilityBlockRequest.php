<?php

namespace App\Modules\Pricing\Http\Requests;

use App\Modules\Pricing\Enums\BlockReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailabilityBlockRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date'],
            'reason' => ['required', Rule::enum(BlockReason::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['unit_id' => __('space'), 'starts_on' => __('start date'), 'ends_on' => __('end date'), 'reason' => __('reason')];
    }
}
