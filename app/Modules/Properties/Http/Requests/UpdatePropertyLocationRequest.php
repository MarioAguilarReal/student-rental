<?php

namespace App\Modules\Properties\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyLocationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'street' => ['required', 'string', 'max:150'],
            'exterior_number' => ['required', 'string', 'max:20'],
            'interior_number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'regex:/^\d{5}$/'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'street' => __('street'), 'exterior_number' => __('exterior number'), 'interior_number' => __('interior number'),
            'neighborhood' => __('neighborhood'), 'city' => __('city'), 'state' => __('state'), 'postal_code' => __('postal code'),
            'latitude' => __('map location'), 'longitude' => __('map location'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['latitude.required' => __('Place the pin on the map.'), 'longitude.required' => __('Place the pin on the map.')];
    }
}
