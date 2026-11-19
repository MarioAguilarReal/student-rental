<?php

namespace App\Modules\Properties\Http\Requests;

use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Verification\Enums\CommunityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::enum(UnitType::class)],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'is_rentable' => ['boolean'],
            'gender_policy' => ['nullable', Rule::enum(GenderPolicy::class)],
            'inherit_communities' => ['boolean'],
            'allowed_communities' => ['array'],
            'allowed_communities.*' => [Rule::enum(CommunityType::class)],
            'amenity_ids' => ['array'],
            'amenity_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['type' => __('type'), 'name' => __('name'), 'capacity' => __('capacity')];
    }

    /**
     * @return list<string>|null null = inherit from the parent space or the property
     */
    public function allowedCommunities(): ?array
    {
        return $this->boolean('inherit_communities', true) ? null : array_values($this->array('allowed_communities'));
    }
}
