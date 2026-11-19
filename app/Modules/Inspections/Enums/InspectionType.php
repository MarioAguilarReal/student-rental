<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-80: first inspection, after a critical change (RN-11), or before the current one expires (RN-85).
 */
enum InspectionType: string
{
    use HasLabel;

    case Initial = 'initial';
    case Change = 'change';
    case Renewal = 'renewal';
}
