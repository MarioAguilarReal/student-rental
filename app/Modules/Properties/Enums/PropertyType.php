<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum PropertyType: string
{
    use HasLabel;

    case House = 'house';
    case Apartment = 'apartment';
    case RoomInHouse = 'room_in_house';
    case Other = 'other';
}
