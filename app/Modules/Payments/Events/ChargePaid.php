<?php

namespace App\Modules\Payments\Events;

use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Models\PaymentAttempt;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-09.01: a charge was paid and posted to the ledger.
 */
class ChargePaid implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Charge $charge,
        public readonly PaymentAttempt $attempt,
    ) {}
}
