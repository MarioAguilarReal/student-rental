<?php

namespace App\Modules\Rentals\Events;

use App\Models\User;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-08.01: a client asked for a space; the operator decides.
 */
class RentalRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Rental $rental,
        public readonly ?User $actor = null,
    ) {}
}
