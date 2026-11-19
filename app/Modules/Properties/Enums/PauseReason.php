<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum PauseReason: string
{
    use HasLabel;

    case Manual = 'manual';
    case InspectionExpired = 'inspection_expired';
}
