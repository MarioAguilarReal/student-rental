<?php

namespace App\Modules\Notifications;

use App\Modules\Inspections\Events\InspectionAssigned;
use App\Modules\Inspections\Events\InspectionCompleted;
use App\Modules\Inspections\Events\InspectionRenewalOpened;
use App\Modules\Notifications\Listeners\SendInspectionNotifications;
use App\Modules\Notifications\Listeners\SendPaymentNotifications;
use App\Modules\Notifications\Listeners\SendRentalNotifications;
use App\Modules\Notifications\Listeners\SendVerificationNotifications;
use App\Modules\Payments\Events\ChargeDueSoon;
use App\Modules\Payments\Events\ChargeOverdue;
use App\Modules\Payments\Events\ChargePaid;
use App\Modules\Payments\Events\ChargePaymentFailed;
use App\Modules\Rentals\Events\RentalApproved;
use App\Modules\Rentals\Events\RentalCancelled;
use App\Modules\Rentals\Events\RentalConfirmed;
use App\Modules\Rentals\Events\RentalExpired;
use App\Modules\Rentals\Events\RentalRejected;
use App\Modules\Rentals\Events\RentalRequested;
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
        RentalRequested::class => [[SendRentalNotifications::class, 'requested']],
        RentalApproved::class => [[SendRentalNotifications::class, 'approved']],
        RentalRejected::class => [[SendRentalNotifications::class, 'rejected']],
        RentalExpired::class => [[SendRentalNotifications::class, 'expired']],
        RentalConfirmed::class => [[SendRentalNotifications::class, 'confirmed']],
        RentalCancelled::class => [[SendRentalNotifications::class, 'cancelled']],
        ChargePaid::class => [[SendPaymentNotifications::class, 'paid']],
        ChargePaymentFailed::class => [[SendPaymentNotifications::class, 'failed']],
        ChargeDueSoon::class => [[SendPaymentNotifications::class, 'dueSoon']],
        ChargeOverdue::class => [[SendPaymentNotifications::class, 'overdue']],
        InspectionRenewalOpened::class => [[SendInspectionNotifications::class, 'renewalOpened']],
    ];
}
