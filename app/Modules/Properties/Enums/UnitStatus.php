<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum UnitStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Paused = 'paused';
}
