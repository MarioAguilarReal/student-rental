<?php

namespace App\Modules\Notifications;

use App\Modules\Inspections\Events\InspectionAssigned;
use App\Modules\Inspections\Events\InspectionCompleted;
use App\Modules\Notifications\Listeners\SendInspectionNotifications;
use App\Modules\Notifications\Listeners\SendVerificationNotifications;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use App\Modules\Verification\Events\VerificationApproved;
use App\Modules\Verification\Events\VerificationExpired;
use App\Modules\Verification\Events\VerificationExpiringSoon;
use App\Modules\Verification\Events\VerificationRejected;

final class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected array $listen = [
        VerificationApproved::class => [[SendVerificationNotifications::class, 'approved']],
        VerificationRejected::class => [[SendVerificationNotifications::class, 'rejected']],
        VerificationExpiringSoon::class => [[SendVerificationNotifications::class, 'expiringSoon']],
        VerificationExpired::class => [[SendVerificationNotifications::class, 'expired']],
        InspectionAssigned::class => [[SendInspectionNotifications::class, 'assigned']],
        InspectionCompleted::class => [[SendInspectionNotifications::class, 'completed']],
    ];
}
