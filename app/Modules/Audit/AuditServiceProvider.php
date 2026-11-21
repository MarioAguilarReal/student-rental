<?php

namespace App\Modules\Audit;

use App\Modules\Audit\Listeners\RecordAuditTrail;
use App\Modules\Identity\Events\InternalRolesChanged;
use App\Modules\Identity\Events\UserReactivated;
use App\Modules\Identity\Events\UserSuspended;
use App\Modules\Inspections\Events\ChecklistPublished;
use App\Modules\Inspections\Events\InspectionAssigned;
use App\Modules\Inspections\Events\InspectionCompleted;
use App\Modules\Payments\Events\ChargeOverdue;
use App\Modules\Payments\Events\ChargePaid;
use App\Modules\Payments\Events\ChargePaymentFailed;
use App\Modules\Payments\Events\CommissionRuleRemoved;
use App\Modules\Payments\Events\CommissionRuleScheduled;
use App\Modules\Payments\Events\PaymentSimulationConfigured;
use App\Modules\Pricing\Events\UnitPricingUpdated;
use App\Modules\Properties\Events\PropertyArchived;
use App\Modules\Properties\Events\PropertyPaused;
use App\Modules\Properties\Events\PropertyPublished;
use App\Modules\Properties\Events\PropertySubmittedForInspection;
use App\Modules\Rentals\Events\RentalApproved;
use App\Modules\Rentals\Events\RentalCancelled;
use App\Modules\Rentals\Events\RentalCompleted;
use App\Modules\Rentals\Events\RentalConfirmed;
use App\Modules\Rentals\Events\RentalExpired;
use App\Modules\Rentals\Events\RentalRejected;
use App\Modules\Rentals\Events\RentalRequested;
use App\Modules\Rentals\Events\RentalStarted;
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
        PropertySubmittedForInspection::class => [[RecordAuditTrail::class, 'propertySubmittedForInspection']],
        PropertyPublished::class => [[RecordAuditTrail::class, 'propertyPublished']],
        PropertyPaused::class => [[RecordAuditTrail::class, 'propertyPaused']],
        PropertyArchived::class => [[RecordAuditTrail::class, 'propertyArchived']],
        UnitPricingUpdated::class => [[RecordAuditTrail::class, 'unitPricingUpdated']],
        InspectionAssigned::class => [[RecordAuditTrail::class, 'inspectionAssigned']],
        InspectionCompleted::class => [[RecordAuditTrail::class, 'inspectionCompleted']],
        ChecklistPublished::class => [[RecordAuditTrail::class, 'checklistPublished']],
        RentalRequested::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalApproved::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalRejected::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalExpired::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalConfirmed::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalStarted::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalCompleted::class => [[RecordAuditTrail::class, 'rentalChanged']],
        RentalCancelled::class => [[RecordAuditTrail::class, 'rentalChanged']],
        ChargePaid::class => [[RecordAuditTrail::class, 'chargeChanged']],
        ChargePaymentFailed::class => [[RecordAuditTrail::class, 'chargeChanged']],
        ChargeOverdue::class => [[RecordAuditTrail::class, 'chargeChanged']],
        CommissionRuleScheduled::class => [[RecordAuditTrail::class, 'commissionRuleScheduled']],
        CommissionRuleRemoved::class => [[RecordAuditTrail::class, 'commissionRuleRemoved']],
        PaymentSimulationConfigured::class => [[RecordAuditTrail::class, 'paymentSimulationConfigured']],
    ];
}
