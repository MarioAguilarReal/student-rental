<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Shared\Enums\SystemParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * HU-03.05: format only; the ranges are business rules (UpdateSystemParameters). Keys contain
 * dots ("rentals.request_hours"), so the form sends them nested: values[rentals][request_hours].
 */
class SystemParametersRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['values' => ['required', 'array']];

        foreach (SystemParameter::cases() as $parameter) {
            $rules["values.{$parameter->value}"] = ['required', 'numeric'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (SystemParameter::cases() as $parameter) {
            $attributes["values.{$parameter->value}"] = $parameter->label();
        }

        return $attributes;
    }

    /**
     * @return array<string, int|float|string>
     */
    public function parameterValues(): array
    {
        $values = $this->input('values', []);

        return is_array($values) ? array_filter(Arr::dot($values), fn ($value) => is_scalar($value) && ! is_bool($value)) : [];
    }
}
