<?php

namespace App\Modules\Identity\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-32: gender declared only for coexistence rules. Optional, asked only when a space
 * restricts it, and never shown to third parties (operators only see "meets the rule").
 */
enum CohabitationGender: string
{
    use HasLabel;

    case Female = 'female';
    case Male = 'male';
}
