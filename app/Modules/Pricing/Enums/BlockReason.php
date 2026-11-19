<?php

namespace App\Modules\Pricing\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum BlockReason: string
{
    use HasLabel;

    case Maintenance = 'maintenance';
    case OwnerUse = 'owner_use';
    case Other = 'other';
}
