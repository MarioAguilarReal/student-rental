<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * HU-04.02: starting layouts for the unit tree. Owners adjust them afterwards.
 */
enum UnitTemplate: string
{
    use HasLabel;

    case EntirePlace = 'entire_place';
    case ByRooms = 'by_rooms';
    case Mixed = 'mixed';
}
