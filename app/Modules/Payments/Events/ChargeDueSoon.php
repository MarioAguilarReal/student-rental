<?php

namespace App\Modules\Payments\Events;

use App\Modules\Payments\Models\Charge;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-10.01: a later charge is due in a few days.
 */
class ChargeDueSoon implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Charge $charge,
    ) {}
}
