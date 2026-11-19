<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum ChecklistCategory: string
{
    use HasLabel;

    case Electrical = 'electrical';
    case Gas = 'gas';
    case FireSafety = 'fire_safety';
    case Security = 'security';
    case Structure = 'structure';
    case Hygiene = 'hygiene';
    case Listing = 'listing';
}
