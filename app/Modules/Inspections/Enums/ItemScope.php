<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-87: property items are answered once (shared areas); unit items once per rentable space.
 */
enum ItemScope: string
{
    use HasLabel;

    case Property = 'property';
    case Unit = 'unit';
}
