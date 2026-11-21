<?php

namespace App\Modules\Payments\Events;

use App\Models\User;
use App\Modules\Payments\Models\CommissionRule;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU-03.04: a rate that had not started yet was removed; the previous one stays open.
 */
class CommissionRuleRemoved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly CommissionRule $rule,
        public readonly User $actor,
    ) {}
}
