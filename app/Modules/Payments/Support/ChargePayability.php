<?php

namespace App\Modules\Payments\Support;

use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Support\Text;

/**
 * HU-09.01: whether a charge can be paid now, shared by PayCharge (which enforces it) and the
 * rental screen (which offers the "Pay" button).
 *
 * - An approved rental only takes its first charge, before the 48 h run out (RN-62, RN-71).
 * - Confirmed and active rentals take their later charges (RN-70, RN-74).
 * - Charges are paid in order, and never twice at the same time (D-75).
 */
final class ChargePayability
{
    /**
     * @return array{rule: string, message: string}|null null when the charge can be paid.
     */
    public static function refusal(Rental $rental, Charge $charge): ?array
    {
        if ($charge->status === ChargeStatus::Paid) {
            return ['rule' => 'HU-09.01', 'message' => Text::of('This charge is already paid.')];
        }

        if (! $charge->isUnpaid()) {
            return ['rule' => 'HU-09.01', 'message' => Text::of('This charge was cancelled.')];
        }

        if ($rental->status === RentalStatus::Approved && ($rental->expires_at === null || ! $rental->expires_at->isFuture())) {
            return ['rule' => 'RN-62', 'message' => Text::of('The time to pay this rental ran out.')];
        }

        if (! in_array($rental->status, [RentalStatus::Approved, RentalStatus::Confirmed, RentalStatus::Active], true)) {
            return ['rule' => 'HU-09.01', 'message' => Text::of('This rental no longer takes payments.')];
        }

        $earlier = $rental->charges()->where('number', '<', $charge->number)->whereIn('status', ChargeStatus::unpaid())->min('number');

        if ($earlier !== null) {
            return ['rule' => 'D-75', 'message' => Text::of('Pay charge :number first.', ['number' => (int) $earlier])];
        }

        if ($charge->attempts()->where('status', PaymentAttemptStatus::Processing)->exists()) {
            return ['rule' => 'D-75', 'message' => Text::of('A payment for this charge is still being processed.')];
        }

        return null;
    }

    /**
     * The charge the client can pay now, if any.
     */
    public static function next(Rental $rental): ?Charge
    {
        $charge = $rental->charges()->whereIn('status', ChargeStatus::unpaid())->orderBy('number')->first();

        return $charge !== null && self::refusal($rental, $charge) === null ? $charge : null;
    }
}
