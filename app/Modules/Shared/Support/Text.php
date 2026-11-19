<?php

namespace App\Modules\Shared\Support;

/**
 * __() may return an array for group keys; UI messages are always single strings.
 */
final class Text
{
    /**
     * @param  array<string, string|int|float>  $replace
     */
    public static function of(string $key, array $replace = []): string
    {
        $translation = __($key, $replace);

        return is_string($translation) ? $translation : $key;
    }
}
