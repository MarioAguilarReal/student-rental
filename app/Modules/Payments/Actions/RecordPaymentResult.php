<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Payments\Events\ChargePaid;
use App\Modules\Payments\Events\ChargePaymentFailed;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Models\PaymentAttempt;
use App\Modules\Payments\Support\GatewayResult;
use App\Modules\Properties\Models\Property;
use App\Modules\Rentals\Actions\ConfirmRental;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\DB;

/**
 * HU-09.01 / HU-09.02: what the gateway answered for an attempt, right away or later.
 *
 * - Succeeded: the charge is paid, the ledger gets its entries (RN-73) and, for the first
 *   charge of an approved rental, the rental is confirmed (RN-71).
 * - Failed: the charge is marked failed and the client can try again.
 * - Processing: only the gateway's reference is kept; the final answer comes later.
 *
 * Idempotent: an attempt that already has a final result is left alone.
 */
class RecordPaymentResult
{
    public function __construct(
        private PostChargeToLedger $ledger,
        private ConfirmRental $confirm,
    ) {}

    public function handle(PaymentAttempt $attempt, GatewayResult $result): PaymentAttempt
    {
        return DB::transaction(function () use ($attempt, $result) {
            // Same lock order as approving and confirming: property, rental, then payments.
            $rentalId = (int) Charge::query()->whereKey($attempt->charge_id)->value('rental_id');
            Property::query()->lockForUpdate()->findOrFail(Rental::query()->whereKey($rentalId)->value('property_id'));
            $rental = Rental::query()->lockForUpdate()->findOrFail($rentalId);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $charge = Charge::query()->lockForUpdate()->findOrFail($attempt->charge_id);

            if ($attempt->status !== PaymentAttemptStatus::Processing) {
                return $attempt;
            }

            $attempt->reference ??= $result->reference;
            $attempt->response = [...($attempt->response ?? []), ...$result->response];

            if ($result->status === PaymentAttemptStatus::Processing) {
                $attempt->save();

                return $attempt;
            }

            // A charge cancelled while its payment was in flight is not paid (no refunds yet, B-01).
            $succeeded = $result->status === PaymentAttemptStatus::Succeeded && $charge->status->canTransitionTo(ChargeStatus::Paid);
            $status = $succeeded ? PaymentAttemptStatus::Succeeded : PaymentAttemptStatus::Failed;

            $attempt->status->assertCanTransitionTo($status);
            $attempt->forceFill(['status' => $status, 'processed_at' => now()])->save();

            if (! $succeeded) {
                if ($charge->status === ChargeStatus::Pending) {
                    $charge->forceFill(['status' => ChargeStatus::Failed])->save();
                }

                ChargePaymentFailed::dispatch($charge, $attempt);

                return $attempt;
            }

            $charge->status->assertCanTransitionTo(ChargeStatus::Paid);
            $charge->forceFill(['status' => ChargeStatus::Paid, 'paid_at' => now()])->save();

            $this->ledger->handle($charge, $rental);

            if ($charge->number === 1 && $rental->status === RentalStatus::Approved) {
                $this->confirm->handle($rental);
            }

            ChargePaid::dispatch($charge, $attempt);

            return $attempt;
        });
    }
}
