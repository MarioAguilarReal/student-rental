<?php

namespace App\Modules\Rentals\Presenters;

use App\Models\User;
use App\Modules\Payments\Models\Charge;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Money;
use App\Modules\Verification\Enums\VerificationKind;

/**
 * Rentals shaped for the client ("My rentals") and for operators and owners.
 *
 * The exact address and the operator's contact reach the client only once the rental is
 * confirmed (HU-08.04). Operators see who the client is, never their documents nor their
 * declared gender (HU-08.02, RN-07, RN-32).
 */
class RentalPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Rental $rental): array
    {
        return [
            'id' => $rental->id,
            'folio' => $rental->folio,
            'status' => $rental->status->value,
            'status_label' => $rental->status->label(),
            'property' => [
                'id' => $rental->property->id,
                'title' => $rental->property->title,
                'location' => $rental->property->approximateLocation(),
            ],
            'unit' => $rental->unit->name,
            'client' => $rental->client->name,
            'period' => $rental->period->value,
            'period_label' => $rental->period->label(),
            'periods' => $rental->periods,
            'starts_on' => $rental->starts_on->toDateString(),
            'ends_on' => $rental->ends_on->toDateString(),
            'total_minor' => $rental->total_minor,
            'deposit_minor' => $rental->deposit_minor,
            'currency' => $rental->currency,
            'expires_at' => $rental->expires_at?->toIso8601String(),
            'requested_at' => $rental->created_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Rental $rental, User $viewer): array
    {
        $rental->loadMissing(['property.owner', 'unit', 'client.verificationProfiles.institution', 'charges']);
        $property = $rental->property;
        $isClient = $rental->client_id === $viewer->id;
        $showAddress = ! $isClient || $rental->status->revealsAddress();
        $commission = Money::ofMinor($rental->total_minor, $rental->currency)->percentage($rental->commission_bp);

        return [
            ...$this->summary($rental),
            'price_minor' => $rental->price_minor,
            'client_message' => $rental->client_message,
            'rejection_reason' => $rental->rejection_reason,
            'cancellation_reason' => $rental->cancellation_reason,
            'decided_at' => $rental->decided_at?->toIso8601String(),
            'cancelled_at' => $rental->cancelled_at?->toIso8601String(),
            'managed' => $rental->management_mode === ManagementMode::Managed,
            'address' => $showAddress ? $property->fullAddress() : null,
            'contact' => $isClient && $rental->status->revealsAddress() ? $this->contact($rental) : null,
            'owner_net_minor' => $isClient ? null : $rental->total_minor - $commission->minorAmount(),
            'commission_bp' => $isClient ? null : $rental->commission_bp,
            'applicant' => $isClient ? null : $this->applicant($rental),
            'charges' => $rental->charges->map(fn (Charge $charge): array => [
                'number' => $charge->number,
                'concept_label' => $charge->concept->label(),
                'amount_minor' => $charge->rent_minor + $charge->deposit_minor,
                'deposit_minor' => $charge->deposit_minor,
                'due_on' => $charge->due_on->toDateString(),
                'status' => $charge->status->value,
                'status_label' => $charge->status->label(),
            ])->values(),
        ];
    }

    /**
     * HU-08.02: what the operator needs to decide. Never documents or the declared gender.
     *
     * @return array<string, mixed>
     */
    private function applicant(Rental $rental): array
    {
        $profile = $rental->client->verificationProfile(VerificationKind::Client);

        return [
            'name' => $rental->client->name,
            'community' => $profile?->community_type?->label(),
            'institution' => $profile?->institution?->name,
            'verification' => $profile?->status->label(),
            'verified' => (bool) $profile?->isVerified(),
        ];
    }

    /**
     * @return array{name: string, email: string}
     */
    private function contact(Rental $rental): array
    {
        if ($rental->management_mode === ManagementMode::Managed) {
            return ['name' => (string) config('app.name'), 'email' => (string) config('mail.from.address')];
        }

        return ['name' => $rental->property->owner->name, 'email' => $rental->property->owner->email];
    }
}
