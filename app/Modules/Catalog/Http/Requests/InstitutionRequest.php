<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstitutionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'acronym' => ['nullable', 'string', 'max:30'],
            'email_domains' => ['required', 'array', 'min:1'],
            'email_domains.*' => ['required', 'string', 'max:255', 'regex:/^@?[a-z0-9-]+(\.[a-z0-9-]+)+$/i'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('name'),
            'acronym' => __('acronym'),
            'email_domains' => __('email domains'),
            'email_domains.*' => __('email domain'),
        ];
    }

    /**
     * Accepts domains as a list or as a comma/space separated string.
     */
    protected function prepareForValidation(): void
    {
        $domains = $this->input('email_domains');

        if (is_string($domains)) {
            $this->merge(['email_domains' => array_values(array_filter(preg_split('/[\s,;]+/', $domains) ?: []))]);
        }
    }
}
