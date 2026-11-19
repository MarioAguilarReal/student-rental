<?php

namespace App\Modules\Catalog\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum AmenityCategory: string
{
    use HasLabel;

    case Basics = 'basics';
    case Kitchen = 'kitchen';
    case Bathroom = 'bathroom';
    case Laundry = 'laundry';
    case Study = 'study';
    case Comfort = 'comfort';
    case Safety = 'safety';
    case Outdoor = 'outdoor';
}
