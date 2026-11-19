<?php

namespace App\Modules\Identity\Events;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class InternalRolesChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public function __construct(
        public readonly User $user,
        public readonly User $actor,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
