<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-72: what the simulated gateway answers. Approving is the default.
 */
enum SimulatedOutcome: string
{
    use HasLabel;

    case Approve = 'approve';
    case Decline = 'decline';
    case Delay = 'delay';
}
