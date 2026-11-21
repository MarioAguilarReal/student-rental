<?php

namespace App\Modules\Payments\Events;

use App\Models\User;
use App\Modules\Payments\Models\PaymentSimulationSetting;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-09.05 · RN-72: the simulated gateway's scenario changed.
 */
class PaymentSimulationConfigured implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentSimulationSetting $setting,
        public readonly User $actor,
    ) {}
}
