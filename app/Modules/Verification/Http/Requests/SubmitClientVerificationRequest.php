<?php

namespace App\Modules\Verification\Http\Requests;

use App\Modules\Verification\Enums\CommunityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Format only. Which documents are required (RN-03) is decided by SubmitClientVerification.
 */
class SubmitClientVerificationRequest extends FormRequest
{
    public const FILE_RULES = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'community_type' => ['required', Rule::enum(CommunityType::class)],
            'institution_id' => ['required', 'integer', Rule::exists('institutions', 'id')],
            'credential' => self::FILE_RULES,
            'acceptance_letter' => self::FILE_RULES,
            'stay_ends_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'community_type' => __('community type'),
            'institution_id' => __('institution'),
            'credential' => __('institutional ID card'),
            'acceptance_letter' => __('acceptance letter'),
            'stay_ends_on' => __('end of stay'),
        ];
    }
}
