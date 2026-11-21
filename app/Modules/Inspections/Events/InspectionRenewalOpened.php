<?php

namespace App\Modules\Inspections\Events;

use App\Modules\Inspections\Models\Inspection;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * RN-85 / HU-10.01 "inspection about to expire": a renewal was opened 30 days before.
 */
class InspectionRenewalOpened implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Inspection $inspection,
    ) {}
}
