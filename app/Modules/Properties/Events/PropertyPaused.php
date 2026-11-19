<?php

namespace App\Modules\Properties\Events;

use App\Models\User;
use App\Modules\Properties\Models\Property;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PropertyPaused implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Property $property,
        public readonly User $actor,
    ) {}
}
