<?php

namespace App\Modules\Pricing\Http\Requests;

use App\Modules\Pricing\Enums\RatePeriod;
use Illuminate\Foundation\Http\FormRequest;

class UnitPricingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['deposit' => ['required', 'numeric', 'min:0', 'max:1000000']];

        foreach (RatePeriod::cases() as $period) {
            $key = "plans.{$period->value}";
            $rules["{$key}.enabled"] = ['required', 'boolean'];
            $rules["{$key}.price"] = ["required_if:{$key}.enabled,true", 'nullable', 'numeric', 'min:0', 'max:1000000'];
            $rules["{$key}.min_periods"] = ["required_if:{$key}.enabled,true", 'nullable', 'integer', 'min:1'];
            $rules["{$key}.max_periods"] = ["required_if:{$key}.enabled,true", 'nullable', 'integer', 'min:1'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['deposit' => __('deposit'), 'plans.*.price' => __('price'), 'plans.*.min_periods' => __('minimum'), 'plans.*.max_periods' => __('maximum')];
    }

    /**
     * Prices arrive in pesos and are stored in cents.
     *
     * @return array<string, array{enabled: bool, price_minor: int, min_periods: int, max_periods: int}>
     */
    public function plans(): array
    {
        $plans = [];

        foreach (RatePeriod::cases() as $period) {
            $plan = (array) $this->input("plans.{$period->value}", []);
            $plans[$period->value] = [
                'enabled' => filter_var($plan['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'price_minor' => self::toMinor($plan['price'] ?? 0),
                'min_periods' => (int) ($plan['min_periods'] ?? 1),
                'max_periods' => (int) ($plan['max_periods'] ?? 1),
            ];
        }

        return $plans;
    }

    public function depositMinor(): int
    {
        return self::toMinor($this->input('deposit', 0));
    }

    private static function toMinor(mixed $pesos): int
    {
        return (int) round(((float) $pesos) * 100);
    }
}
