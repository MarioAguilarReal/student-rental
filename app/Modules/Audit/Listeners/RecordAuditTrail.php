<?php

namespace App\Modules\Audit\Listeners;

use App\Modules\Identity\Events\InternalRolesChanged;
use App\Modules\Identity\Events\UserReactivated;
use App\Modules\Identity\Events\UserSuspended;
use App\Modules\Pricing\Events\UnitPricingUpdated;
use App\Modules\Properties\Events\PropertyArchived;
use App\Modules\Properties\Events\PropertyPaused;
use App\Modules\Properties\Events\PropertyPublished;
use App\Modules\Properties\Events\PropertySubmittedForInspection;
use App\Modules\Verification\Events\VerificationApproved;
use App\Modules\Verification\Events\VerificationExpired;
use App\Modules\Verification\Events\VerificationRejected;
use App\Modules\Verification\Events\VerificationSubmitted;

/**
 * RN-90 / HU-11.01: who changed what and when. Events are dispatched after commit,
 * so only changes that really happened are recorded.
 */
class RecordAuditTrail
{
    public function verificationSubmitted(VerificationSubmitted $event): void
    {
        activity('verification')->performedOn($event->profile)->causedBy($event->profile->user)
            ->event('submitted')->withProperties(['kind' => $event->profile->kind->value])
            ->log('Verification submitted');
    }

    public function verificationApproved(VerificationApproved $event): void
    {
        activity('verification')->performedOn($event->profile)->causedBy($event->reviewer)
            ->event('approved')->withProperties(['verified_until' => $event->profile->verified_until?->toDateString()])
            ->log('Verification approved');
    }

    public function verificationRejected(VerificationRejected $event): void
    {
        activity('verification')->performedOn($event->profile)->causedBy($event->reviewer)
            ->event('rejected')->withProperties(['reason' => $event->profile->rejection_reason])
            ->log('Verification rejected');
    }

    public function verificationExpired(VerificationExpired $event): void
    {
        activity('verification')->performedOn($event->profile)->causedByAnonymous()
            ->event('expired')->log('Verification expired');
    }

    public function rolesChanged(InternalRolesChanged $event): void
    {
        activity('identity')->performedOn($event->user)->causedBy($event->actor)
            ->event('roles_changed')->withProperties(['before' => $event->before, 'after' => $event->after])
            ->log('Roles changed');
    }

    public function userSuspended(UserSuspended $event): void
    {
        activity('identity')->performedOn($event->user)->causedBy($event->actor)
            ->event('suspended')->withProperties(['reason' => $event->reason])
            ->log('Account suspended');
    }

    public function userReactivated(UserReactivated $event): void
    {
        activity('identity')->performedOn($event->user)->causedBy($event->actor)
            ->event('reactivated')->log('Account reactivated');
    }

    public function propertySubmittedForInspection(PropertySubmittedForInspection $event): void
    {
        activity('property')->performedOn($event->property)->causedByAnonymous()
            ->event('submitted_for_inspection')->withProperties(['reason' => $event->reason])
            ->log('Property submitted for inspection');
    }

    public function propertyPublished(PropertyPublished $event): void
    {
        activity('property')->performedOn($event->property)->causedBy($event->actor)->event('published')->log('Property published');
    }

    public function propertyPaused(PropertyPaused $event): void
    {
        activity('property')->performedOn($event->property)->causedBy($event->actor)->event('paused')->log('Property paused');
    }

    public function propertyArchived(PropertyArchived $event): void
    {
        activity('property')->performedOn($event->property)->causedBy($event->actor)->event('archived')->log('Property archived');
    }

    public function unitPricingUpdated(UnitPricingUpdated $event): void
    {
        activity('pricing')->performedOn($event->unit)->causedBy($event->actor)
            ->event('pricing_updated')->withProperties(['before' => $event->before, 'after' => $event->after])
            ->log('Prices updated');
    }
}
