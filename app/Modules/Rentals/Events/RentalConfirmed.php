<?php

namespace App\Modules\Rentals\Events;

use App\Models\User;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * RN-71: the first charge was paid.
 */
class RentalConfirmed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Rental $rental,
        public readonly ?User $actor = null,
    ) {}
}
