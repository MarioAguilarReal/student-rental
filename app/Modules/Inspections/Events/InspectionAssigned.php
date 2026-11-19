<?php

namespace App\Modules\Inspections\Events;

use App\Models\User;
use App\Modules\Inspections\Models\Inspection;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class InspectionAssigned implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Inspection $inspection,
        public readonly User $actor,
    ) {}
}
