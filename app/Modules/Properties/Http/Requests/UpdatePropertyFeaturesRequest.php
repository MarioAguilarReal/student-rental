<?php

namespace App\Modules\Properties\Http\Requests;

use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Verification\Enums\CommunityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyFeaturesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amenity_ids' => ['present', 'array'],
            'amenity_ids.*' => ['integer'],
            'house_rules' => ['present', 'array'],
            'house_rules.*.id' => ['required', 'integer'],
            'house_rules.*.is_allowed' => ['required', 'boolean'],
            'house_rules.*.note' => ['nullable', 'string', 'max:255'],
            'gender_policy' => ['required', Rule::enum(GenderPolicy::class)],
            'allowed_communities' => ['present', 'array'],
            'allowed_communities.*' => [Rule::enum(CommunityType::class)],
        ];
    }

    /**
     * @return array<int, array{is_allowed: bool, note: ?string}>
     */
    public function houseRules(): array
    {
        $rules = [];

        foreach ($this->array('house_rules') as $rule) {
            $rules[(int) $rule['id']] = ['is_allowed' => (bool) $rule['is_allowed'], 'note' => $rule['note'] ?? null];
        }

        return $rules;
    }
}
