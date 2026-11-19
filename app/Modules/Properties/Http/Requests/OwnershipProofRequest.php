<?php

namespace App\Modules\Properties\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnershipProofRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['document' => __('proof of ownership')];
    }
}
