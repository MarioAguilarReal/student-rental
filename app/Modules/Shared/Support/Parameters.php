<?php

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Models\SystemParameterValue;
use Illuminate\Support\Facades\Cache;

/**
 * HU-03.05: reads a system parameter (the Super Admin's value or the default). Cached;
 * the cache is cleared whenever a value changes.
 */
final class Parameters
{
    private const CACHE_KEY = 'system_parameters';

    public static function int(SystemParameter $parameter): int
    {
        return (int) round(self::value($parameter));
    }

    public static function decimal(SystemParameter $parameter): float
    {
        return self::value($parameter);
    }

    /**
     * @return array<string, int|float> every parameter with its current value
     */
    public static function all(): array
    {
        $stored = self::stored();

        $values = [];

        foreach (SystemParameter::cases() as $parameter) {
            $value = isset($stored[$parameter->value]) ? (float) $stored[$parameter->value] : $parameter->default();
            $values[$parameter->value] = $parameter->isDecimal() ? (float) $value : (int) round($value);
        }

        return $values;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function value(SystemParameter $parameter): float
    {
        $stored = self::stored();

        return isset($stored[$parameter->value]) ? (float) $stored[$parameter->value] : (float) $parameter->default();
    }

    /**
     * @return array<string, string>
     */
    private static function stored(): array
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => SystemParameterValue::query()->pluck('value', 'key')->all());

        return array_map(strval(...), $rows);
    }
}
