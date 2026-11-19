<?php

namespace App\Modules\Shared\Enums\Concerns;

/**
 * Translated labels for enum cases, read from lang/{locale}/enums.php:
 * 'EnumClassBasename' => ['case_value' => 'Label'].
 *
 * @phpstan-require-implements \BackedEnum
 */
trait HasLabel
{
    public function label(): string
    {
        // Read the whole array: case values may contain dots (e.g. "properties.create"),
        // which the translator would otherwise treat as nested keys.
        $labels = __('enums.'.class_basename(static::class));
        $label = is_array($labels) ? ($labels[$this->value] ?? null) : null;

        return is_string($label) ? $label : (string) $this->value;
    }

    /**
     * @return list<array{value: string|int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
