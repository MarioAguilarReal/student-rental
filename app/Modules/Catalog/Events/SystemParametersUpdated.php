<?php

namespace App\Modules\Catalog\Events;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-03.05: the Super Admin changed system parameters (RN-90: before and after).
 */
class SystemParametersUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  array<string, int|float>  $before
     * @param  array<string, int|float>  $after
     */
    public function __construct(
        public readonly User $actor,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
