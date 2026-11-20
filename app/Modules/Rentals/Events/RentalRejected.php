<?php

namespace App\Modules\Rentals\Events;

use App\Models\User;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-08.02 / RN-24: rejected by the operator (with a reason, RN-67) or automatically (actor null).
 */
class RentalRejected implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Rental $rental,
        public readonly ?User $actor = null,
    ) {}
}
