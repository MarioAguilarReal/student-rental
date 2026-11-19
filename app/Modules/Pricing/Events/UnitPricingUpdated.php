<?php

namespace App\Modules\Pricing\Events;

use App\Models\User;
use App\Modules\Properties\Models\Unit;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class UnitPricingUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly Unit $unit,
        public readonly User $actor,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
