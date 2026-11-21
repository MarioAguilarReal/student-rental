<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Models\PaymentAttempt;
use App\Modules\Payments\Support\ChargePayability;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-09.01: the client pays a charge with the gateway (D-05; simulated in the MVP, RN-72).
 *
 * The attempt is saved first (one in flight per charge, D-75), the gateway is called outside
 * the transaction and its answer is recorded by RecordPaymentResult: paying the first charge
 * confirms the rental (RN-71); a declined payment can be retried.
 */
class PayCharge
{
    public function __construct(
        private PaymentGateway $gateway,
        private RecordPaymentResult $record,
    ) {}

    public function handle(User $client, Charge $charge): PaymentAttempt
    {
        $attempt = DB::transaction(function () use ($client, $charge) {
            $rental = Rental::query()->lockForUpdate()->findOrFail($charge->rental_id);
            $charge = Charge::query()->lockForUpdate()->findOrFail($charge->id);

            if ($rental->client_id !== $client->id) {
                throw new BusinessRuleViolation('HU-09.01', Text::of('Only the client of the rental can pay its charges.'));
            }

            if ($refusal = ChargePayability::refusal($rental, $charge)) {
                throw new BusinessRuleViolation($refusal['rule'], $refusal['message']);
            }

            return PaymentAttempt::create([
                'charge_id' => $charge->id,
                'client_id' => $client->id,
                'gateway' => $this->gateway->name(),
                'amount_minor' => $charge->amount()->minorAmount(),
                'currency' => $charge->currency,
                'status' => PaymentAttemptStatus::Processing,
            ]);
        });

        $result = $this->gateway->charge(
            $attempt->key(),
            $charge->amount(),
            Text::of('Charge :number of rental :folio', ['number' => $charge->number, 'folio' => (string) $charge->rental->folio]),
        );

        return $this->record->handle($attempt, $result);
    }
}
