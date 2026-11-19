<?php

namespace App\Modules\Verification\Events;

use App\Models\User;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class VerificationApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly VerificationProfile $profile,
        public readonly User $reviewer,
    ) {}
}
