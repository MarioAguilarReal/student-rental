<?php

namespace App\Modules\Verification\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum DocumentStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
