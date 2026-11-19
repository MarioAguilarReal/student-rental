<?php

namespace App\Modules\Audit;

use App\Modules\Audit\Listeners\RecordAuditTrail;
use App\Modules\Identity\Events\InternalRolesChanged;
use App\Modules\Identity\Events\UserReactivated;
use App\Modules\Identity\Events\UserSuspended;
use App\Modules\Shared\Providers\ModuleServiceProvider;
use App\Modules\Verification\Events\VerificationApproved;
use App\Modules\Verification\Events\VerificationExpired;
use App\Modules\Verification\Events\VerificationRejected;
use App\Modules\Verification\Events\VerificationSubmitted;

final class AuditServiceProvider extends ModuleServiceProvider
{
    protected array $listen = [
        VerificationSubmitted::class => [[RecordAuditTrail::class, 'verificationSubmitted']],
        VerificationApproved::class => [[RecordAuditTrail::class, 'verificationApproved']],
        VerificationRejected::class => [[RecordAuditTrail::class, 'verificationRejected']],
        VerificationExpired::class => [[RecordAuditTrail::class, 'verificationExpired']],
        InternalRolesChanged::class => [[RecordAuditTrail::class, 'rolesChanged']],
        UserSuspended::class => [[RecordAuditTrail::class, 'userSuspended']],
        UserReactivated::class => [[RecordAuditTrail::class, 'userReactivated']],
    ];
}
