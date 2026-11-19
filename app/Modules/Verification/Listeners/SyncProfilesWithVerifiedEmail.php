<?php

namespace App\Modules\Verification\Listeners;

use App\Models\User;
use App\Modules\Verification\Actions\MarkEmailVerified;
use Illuminate\Auth\Events\Verified;

/**
 * HU-02.01: runs synchronously so the verification page is ready right after the link is clicked.
 */
class SyncProfilesWithVerifiedEmail
{
    public function __construct(private MarkEmailVerified $markEmailVerified) {}

    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->markEmailVerified->handle($event->user);
        }
    }
}
