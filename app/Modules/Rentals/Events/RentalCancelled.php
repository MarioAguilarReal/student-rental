<?php

namespace App\Modules\Rentals\Events;

use App\Models\User;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-08.07 · RN-66: an administrator cancelled the rental.
 */
class RentalCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Rental $rental,
        public readonly ?User $actor = null,
    ) {}
}
