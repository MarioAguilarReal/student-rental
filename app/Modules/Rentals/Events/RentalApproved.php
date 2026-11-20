<?php

namespace App\Modules\Rentals\Events;

use App\Models\User;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-08.02: the operator approved; the client has 48 h to pay the first charge (RN-62).
 */
class RentalApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Rental $rental,
        public readonly ?User $actor = null,
    ) {}
}
