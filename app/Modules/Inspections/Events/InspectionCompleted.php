<?php

namespace App\Modules\Inspections\Events;

use App\Modules\Inspections\Models\Inspection;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class InspectionCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Inspection $inspection,
    ) {}
}
