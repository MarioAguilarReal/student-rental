<?php

namespace App\Modules\Notifications\Listeners;

use App\Models\User;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Notifications\Support\RentalOperators;
use App\Modules\Rentals\Events\RentalApproved;
use App\Modules\Rentals\Events\RentalCancelled;
use App\Modules\Rentals\Events\RentalConfirmed;
use App\Modules\Rentals\Events\RentalExpired;
use App\Modules\Rentals\Events\RentalRejected;
use App\Modules\Rentals\Events\RentalRequested;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * HU-10.01 for rentals. The operator is the owner of a self-managed property or the
 * administrators of a managed one (RN-60).
 */
class SendRentalNotifications
{
    public function requested(RentalRequested $event): void
    {
        $rental = $event->rental;

        Notification::send($this->operators($rental), $this->message(
            $rental,
            'rental.requested',
            'New rental request for :property',
            ':client wants :unit from :start to :end. Answer within 72 hours.',
            'Review request',
        ));
    }

    public function approved(RentalApproved $event): void
    {
        $rental = $event->rental;

        $rental->client->notify($this->message(
            $rental,
            'rental.approved',
            'Your request for :property was approved',
            'Pay the first charge within 48 hours to confirm your rental.',
            'View rental',
        ));
    }

    public function rejected(RentalRejected $event): void
    {
        $rental = $event->rental;

        $rental->client->notify($this->message(
            $rental,
            'rental.rejected',
            'Your request for :property was not approved',
            'Reason: :reason',
            'View request',
            ['reason' => (string) $rental->rejection_reason],
        ));
    }

    public function expired(RentalExpired $event): void
    {
        $rental = $event->rental;

        Notification::send($this->operators($rental)->push($rental->client), $this->message(
            $rental,
            'rental.expired',
            'Rental :folio expired',
            'The request for :property was not completed in time, so the dates are free again.',
            'View rental',
        ));
    }

    public function confirmed(RentalConfirmed $event): void
    {
        $rental = $event->rental;

        Notification::send($this->operators($rental)->push($rental->client), $this->message(
            $rental,
            'rental.confirmed',
            'Rental :folio is confirmed',
            ':unit at :property is reserved from :start to :end.',
            'View rental',
        ));
    }

    public function cancelled(RentalCancelled $event): void
    {
        $rental = $event->rental;
        $recipients = $this->operators($rental)->push($rental->client)->push($rental->property->owner)->unique('id');

        Notification::send($recipients, $this->message(
            $rental,
            'rental.cancelled',
            'Rental :folio was cancelled',
            'An administrator cancelled the rental of :unit at :property. Reason: :reason',
            'View rental',
            ['reason' => (string) $rental->cancellation_reason],
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function operators(Rental $rental): Collection
    {
        return RentalOperators::of($rental);
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function message(Rental $rental, string $type, string $subject, string $body, string $action, array $extra = []): PlatformMessage
    {
        return new PlatformMessage($type, $subject, $body, $action, route('rentals.show', $rental, absolute: false), [
            'folio' => (string) $rental->folio,
            'property' => $rental->property->title,
            'unit' => $rental->unit->name,
            'client' => $rental->client->name,
            'start' => $rental->starts_on->toDateString(),
            'end' => $rental->ends_on->toDateString(),
            ...$extra,
        ]);
    }
}
