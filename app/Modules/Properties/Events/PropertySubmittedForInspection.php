<?php

namespace App\Modules\Properties\Events;

use App\Modules\Properties\Models\Property;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PropertySubmittedForInspection implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Property $property,
        /** initial | resubmission | change (RN-11) */
        public readonly string $reason,
    ) {}
}
