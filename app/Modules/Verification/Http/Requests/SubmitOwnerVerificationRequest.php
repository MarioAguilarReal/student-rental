<?php

namespace App\Modules\Verification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOwnerVerificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'official_id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['official_id' => __('official ID')];
    }
}
