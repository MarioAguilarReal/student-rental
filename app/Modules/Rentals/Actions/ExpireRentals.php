<?php

namespace App\Modules\Rentals\Actions;

use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;

/**
 * HU-08.03 · RN-61 / RN-62: scheduled sweep of requests and approvals past their deadline.
 */
class ExpireRentals
{
    public function __construct(private ExpireRental $expire) {}

    public function handle(?int $propertyId = null): int
    {
        $expired = 0;

        Rental::query()
            ->whereIn('status', [RentalStatus::Requested, RentalStatus::Approved])
            ->where('expires_at', '<=', now())
            ->when($propertyId, fn ($query, $id) => $query->where('property_id', $id))
            ->orderBy('id')
            ->each(function (Rental $rental) use (&$expired) {
                $expired += $this->expire->handle($rental) ? 1 : 0;
            });

        return $expired;
    }
}
