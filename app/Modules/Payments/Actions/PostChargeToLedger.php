<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\EntryDirection;
use App\Modules\Payments\Enums\LedgerAccount;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Models\LedgerEntry;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * HU-09.02 · RN-73: one balanced transaction per paid charge.
 *
 * - Rent: Client → PlatformCommission (commission) + OwnerPayable (rent − commission).
 * - Deposit: Client → DepositsHeld.
 *
 * The commission is the rental's own copy (RN-45), only on the rent (RN-51), and is taken
 * from the owner (RN-52). Entries are never edited (RN-75).
 */
class PostChargeToLedger
{
    public function handle(Charge $charge, Rental $rental): string
    {
        $rent = Money::ofMinor($charge->rent_minor, $charge->currency);
        $deposit = Money::ofMinor($charge->deposit_minor, $charge->currency);
        $commission = $rent->percentage($rental->commission_bp);
        $ownerId = (int) $rental->property()->value('owner_id');

        $credits = [
            [LedgerAccount::PlatformCommission, null, $commission],
            [LedgerAccount::OwnerPayable, $ownerId, $rent->minus($commission)],
            [LedgerAccount::DepositsHeld, $rental->client_id, $deposit],
        ];

        $total = array_reduce($credits, fn (Money $sum, array $credit) => $sum->plus($credit[2]), Money::zero($charge->currency));

        if (! $total->equals($charge->amount())) {
            throw new LogicException("Ledger transaction for charge {$charge->id} does not balance.");
        }

        $transactionId = (string) Str::uuid();

        DB::transaction(function () use ($charge, $rental, $transactionId, $credits) {
            $this->entry($transactionId, $charge, $rental, LedgerAccount::Client, $rental->client_id, EntryDirection::Debit, $charge->amount());

            foreach ($credits as [$account, $holderId, $amount]) {
                if (! $amount->isZero()) {
                    $this->entry($transactionId, $charge, $rental, $account, $holderId, EntryDirection::Credit, $amount);
                }
            }
        });

        return $transactionId;
    }

    private function entry(string $transactionId, Charge $charge, Rental $rental, LedgerAccount $account, ?int $holderId, EntryDirection $direction, Money $amount): void
    {
        LedgerEntry::create([
            'transaction_id' => $transactionId,
            'account' => $account,
            'holder_id' => $holderId,
            'rental_id' => $rental->id,
            'charge_id' => $charge->id,
            'direction' => $direction,
            'amount_minor' => $amount->minorAmount(),
            'currency' => $charge->currency,
        ]);
    }
}
