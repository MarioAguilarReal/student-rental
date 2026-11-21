<?php

namespace App\Modules\Audit\Http\Requests;

use App\Modules\Audit\Enums\AuditLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HU-11.01: filters by area, actor and dates.
 */
class AuditLogRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'log' => ['nullable', Rule::enum(AuditLog::class)],
            'actor' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['log' => __('area'), 'actor' => __('actor'), 'from' => __('from'), 'to' => __('to')];
    }
}
