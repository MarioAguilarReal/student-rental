<?php

namespace App\Modules\Payments\Events;

use App\Modules\Payments\Models\Charge;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * RN-74: a charge was not paid by its due date.
 */
class ChargeOverdue implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Charge $charge,
    ) {}
}
