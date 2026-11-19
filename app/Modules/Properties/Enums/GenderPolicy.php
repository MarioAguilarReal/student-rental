<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-30: a unit may only be equal to or stricter than its parent; never looser.
 */
enum GenderPolicy: string
{
    use HasLabel;

    case Mixed = 'mixed';
    case WomenOnly = 'women_only';
    case MenOnly = 'men_only';

    public function allowsChild(self $child): bool
    {
        return $this === self::Mixed || $child === $this;
    }
}
