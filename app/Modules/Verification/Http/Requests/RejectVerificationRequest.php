<?php

namespace App\Modules\Verification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectVerificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => __('reason')];
    }
}
