<?php

namespace App\Modules\Identity\Events;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class UserReactivated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly User $actor,
    ) {}
}
