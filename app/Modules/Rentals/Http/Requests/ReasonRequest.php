<?php

namespace App\Modules\Rentals\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A rejection (RN-67) or a cancellation (RN-66): both need a reason the client will read.
 */
class ReasonRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => __('reason')];
    }
}
