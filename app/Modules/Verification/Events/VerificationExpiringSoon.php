<?php

namespace App\Modules\Verification\Events;

use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class VerificationExpiringSoon implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly VerificationProfile $profile,
    ) {}
}
