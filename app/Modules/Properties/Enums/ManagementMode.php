<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * D-01: who operates the property (approves rentals). Commission depends on it (RN-50).
 */
enum ManagementMode: string
{
    use HasLabel;

    case SelfManaged = 'self_managed';
    case Managed = 'managed';
}
