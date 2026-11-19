<?php

namespace App\Modules\Inspections\Events;

use App\Models\User;
use App\Modules\Inspections\Models\ChecklistTemplate;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ChecklistPublished implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly ChecklistTemplate $template,
        public readonly User $actor,
    ) {}
}
