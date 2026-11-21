<?php

namespace App\Modules\Notifications\Listeners;

use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Notifications\Support\RentalOperators;
use App\Modules\Payments\Events\ChargeOverdue;
use App\Modules\Payments\Events\ChargePaid;
use App\Modules\Payments\Events\ChargePaymentFailed;
use App\Modules\Payments\Models\Charge;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;

/**
 * HU-10.01 for payments: the client's receipt and declined payments (HU-09.01), and overdue
 * charges to the client and the operator (RN-74).
 */
class SendPaymentNotifications
{
    public function paid(ChargePaid $event): void
    {
        $event->charge->rental->client->notify($this->message(
            $event->charge,
            'charge.paid',
            'Payment received for rental :folio',
            'We received :amount for charge :number (test payment). Reference: :reference',
            'View rental',
            ['reference' => (string) $event->attempt->reference],
        ));
    }

    public function failed(ChargePaymentFailed $event): void
    {
        $event->charge->rental->client->notify($this->message(
            $event->charge,
            'charge.failed',
            'Your payment for rental :folio was declined',
            'The payment of :amount for charge :number did not go through. You can try again from your rental.',
            'Try again',
        ));
    }

    public function overdue(ChargeOverdue $event): void
    {
        $rental = $event->charge->rental;

        Notification::send(RentalOperators::of($rental)->push($rental->client)->unique('id'), $this->message(
            $event->charge,
            'charge.overdue',
            'Charge :number of rental :folio is overdue',
            'The charge of :amount was due on :due and has not been paid.',
            'View rental',
        ));
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function message(Charge $charge, string $type, string $subject, string $body, string $action, array $extra = []): PlatformMessage
    {
        $rental = $charge->rental;

        return new PlatformMessage($type, $subject, $body, $action, route('rentals.show', $rental, absolute: false), [
            'folio' => (string) $rental->folio,
            'property' => $rental->property->title,
            'number' => (string) $charge->number,
            'amount' => $this->format($charge),
            'due' => $charge->due_on->toDateString(),
            ...$extra,
        ]);
    }

    private function format(Charge $charge): string
    {
        return (string) Number::currency($charge->amount()->minorAmount() / 100, $charge->currency, 'es_MX');
    }
}
