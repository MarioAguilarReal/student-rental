<?php

namespace App\Modules\Audit\Listeners;

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
use App\Modules\Rentals\Models\Rental;
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

    public function inspectionAssigned(InspectionAssigned $event): void
    {
        activity('inspection')->performedOn($event->inspection)->causedBy($event->actor)->event('assigned')
            ->withProperties(['inspector_id' => $event->inspection->inspector_id, 'scheduled_for' => $event->inspection->scheduled_for?->toDateString()])
            ->log('Inspection assigned');
    }

    public function inspectionCompleted(InspectionCompleted $event): void
    {
        activity('inspection')->performedOn($event->inspection)->causedBy($event->inspection->inspector)->event('completed')
            ->withProperties(['result' => $event->inspection->result?->value, 'property_id' => $event->inspection->property_id])
            ->log('Inspection completed');
    }

    public function checklistPublished(ChecklistPublished $event): void
    {
        activity('inspection')->performedOn($event->template)->causedBy($event->actor)->event('checklist_published')
            ->withProperties(['version' => $event->template->version])
            ->log('Checklist published');
    }

    /**
     * RN-90: every rental status change, with its commercial copy when it is created.
     */
    public function rentalChanged(RentalRequested|RentalApproved|RentalRejected|RentalExpired|RentalConfirmed|RentalStarted|RentalCompleted|RentalCancelled $event): void
    {
        $rental = $event->rental;
        $logger = activity('rental')->performedOn($rental);
        $logger = $event->actor ? $logger->causedBy($event->actor) : $logger->causedByAnonymous();

        $logger->event($rental->status->value)
            ->withProperties(array_filter([
                'status' => $rental->status->value,
                'reason' => $rental->rejection_reason ?? $rental->cancellation_reason,
                'commercial' => $event instanceof RentalRequested ? $this->commercialCopy($rental) : null,
            ]))
            ->log('Rental '.$rental->status->value);
    }

    /**
     * HU-09.01 / RN-74: payments and overdue charges, with the gateway's reference.
     */
    public function chargeChanged(ChargePaid|ChargePaymentFailed|ChargeOverdue $event): void
    {
        $charge = $event->charge;
        $attempt = $event instanceof ChargeOverdue ? null : $event->attempt;
        $logger = activity('payment')->performedOn($charge);
        $logger = $attempt ? $logger->causedBy($attempt->client) : $logger->causedByAnonymous();
        $name = match (true) {
            $event instanceof ChargePaid => 'paid',
            $event instanceof ChargePaymentFailed => 'failed',
            default => 'overdue',
        };

        $logger->event($name)
            ->withProperties(array_filter([
                'rental_id' => $charge->rental_id,
                'number' => $charge->number,
                'amount_minor' => $charge->rent_minor + $charge->deposit_minor,
                'gateway' => $attempt?->gateway,
                'reference' => $attempt?->reference,
            ], fn ($value) => $value !== null))
            ->log('Charge '.$name);
    }

    public function commissionRuleScheduled(CommissionRuleScheduled $event): void
    {
        activity('payment')->performedOn($event->rule)->causedBy($event->actor)
            ->event('commission_scheduled')
            ->withProperties([
                'management_mode' => $event->rule->management_mode->value,
                'percentage_bp' => $event->rule->percentage_bp,
                'valid_from' => $event->rule->valid_from->toDateString(),
            ])
            ->log('Commission rate scheduled');
    }

    public function commissionRuleRemoved(CommissionRuleRemoved $event): void
    {
        activity('payment')->causedBy($event->actor)
            ->event('commission_removed')
            ->withProperties([
                'management_mode' => $event->rule->management_mode->value,
                'percentage_bp' => $event->rule->percentage_bp,
                'valid_from' => $event->rule->valid_from->toDateString(),
            ])
            ->log('Scheduled commission rate removed');
    }

    public function paymentSimulationConfigured(PaymentSimulationConfigured $event): void
    {
        activity('payment')->performedOn($event->setting)->causedBy($event->actor)
            ->event('simulation_configured')
            ->withProperties(['outcome' => $event->setting->outcome->value, 'delay_seconds' => $event->setting->delay_seconds])
            ->log('Payment simulation configured');
    }

    /**
     * @return array<string, mixed>
     */
    private function commercialCopy(Rental $rental): array
    {
        return [
            'period' => $rental->period->value,
            'periods' => $rental->periods,
            'price_minor' => $rental->price_minor,
            'deposit_minor' => $rental->deposit_minor,
            'management_mode' => $rental->management_mode->value,
            'commission_bp' => $rental->commission_bp,
        ];
    }
}
