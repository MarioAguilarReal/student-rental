<?php

namespace App\Modules\Search\Http\Requests;

use App\Modules\Catalog\Models\Campus;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Search\Support\CatalogFilters;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Search filters arrive in the query string; anything invalid is ignored rather than failing the page.
 */
class CatalogRequest extends FormRequest
{
    public const RADII = [1, 2, 5, 10, 20];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'campus' => ['nullable', 'integer'],
            'radius' => ['nullable', Rule::in(self::RADII)],
            'period' => ['nullable', Rule::enum(RatePeriod::class)],
            'type' => ['nullable', Rule::enum(UnitType::class)],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'move_in' => ['nullable', 'date'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer'],
            'gender' => ['nullable', Rule::enum(GenderPolicy::class)],
            'sort' => ['nullable', Rule::in(['recommended', 'price', 'distance'])],
        ];
    }

    public function filters(): CatalogFilters
    {
        $data = $this->safe();
        $moveIn = $data['move_in'] ?? null;

        return new CatalogFilters(
            period: RatePeriod::tryFrom((string) ($data['period'] ?? '')) ?? RatePeriod::Monthly,
            campus: isset($data['campus']) ? Campus::query()->whereKey((int) $data['campus'])->first() : null,
            radiusKm: (float) ($data['radius'] ?? 5),
            unitType: UnitType::tryFrom((string) ($data['type'] ?? '')),
            minPriceMinor: isset($data['min_price']) ? (int) round((float) $data['min_price'] * 100) : null,
            maxPriceMinor: isset($data['max_price']) ? (int) round((float) $data['max_price'] * 100) : null,
            moveIn: $moveIn ? CarbonImmutable::parse($moveIn)->startOfDay() : null,
            amenityIds: array_values(array_map('intval', (array) ($data['amenities'] ?? []))),
            genderPolicy: GenderPolicy::tryFrom((string) ($data['gender'] ?? '')),
            sort: (string) ($data['sort'] ?? 'recommended'),
        );
    }

    /**
     * Invalid filters fall back to defaults instead of a validation error page.
     */
    protected function failedValidation(Validator $validator): void
    {
        $this->replace(array_diff_key($this->all(), $validator->errors()->messages()));
        $this->setValidator(validator($this->all(), $this->rules()));
    }
}
