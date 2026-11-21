<?php

namespace App\Modules\Payments\Support;

use App\Models\User;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Enums\EntryDirection;
use App\Modules\Payments\Enums\LedgerAccount;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Payments\Models\LedgerEntry;
use App\Modules\Payments\Models\PaymentAttempt;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read model over the ledger (RN-73): the owner's income (HU-09.03) and the administrators'
 * financial panel (HU-09.04). Every amount comes from the ledger or from the charges, never
 * recalculated from prices (RN-75).
 */
final class FinanceReport
{
    /**
     * HU-09.03: gross rent collected, commission and net for the owner's rentals.
     *
     * @return array{totals: array{gross_minor: int, commission_minor: int, net_minor: int, deposits_minor: int, upcoming_minor: int}, rentals: list<array<string, mixed>>}
     */
    public function ownerBalance(User $owner): array
    {
        $rentals = Rental::query()
            ->whereHas('property', fn (Builder $property) => $property->where('owner_id', $owner->id))
            ->where(fn (Builder $query) => $query
                ->whereHas('charges', fn (Builder $charge) => $charge->where('status', ChargeStatus::Paid))
                ->orWhereIn('status', [RentalStatus::Confirmed, RentalStatus::Active]))
            ->with(['property', 'unit', 'client'])
            ->latest('starts_on')
            ->get();

        $ids = array_values($rentals->map(fn (Rental $rental): int => $rental->id)->all());
        $credits = $this->creditsByRental($ids);
        $upcoming = $this->upcomingRentByRental($ids);

        $rows = array_values($rentals->map(function (Rental $rental) use ($credits, $upcoming): array {
            $commission = $credits[$rental->id][LedgerAccount::PlatformCommission->value] ?? 0;
            $net = $credits[$rental->id][LedgerAccount::OwnerPayable->value] ?? 0;

            return [
                'id' => $rental->id,
                'folio' => $rental->folio,
                'property' => $rental->property->title,
                'unit' => $rental->unit->name,
                'client' => $rental->client->name,
                'status' => $rental->status->value,
                'status_label' => $rental->status->label(),
                'starts_on' => $rental->starts_on->toDateString(),
                'ends_on' => $rental->ends_on->toDateString(),
                'commission_bp' => $rental->commission_bp,
                'gross_minor' => $commission + $net,
                'commission_minor' => $commission,
                'net_minor' => $net,
                'upcoming_minor' => $upcoming[$rental->id] ?? 0,
            ];
        })->all());

        return [
            'totals' => [
                'gross_minor' => array_sum(array_column($rows, 'gross_minor')),
                'commission_minor' => array_sum(array_column($rows, 'commission_minor')),
                'net_minor' => array_sum(array_column($rows, 'net_minor')),
                'deposits_minor' => (int) collect($credits)->sum(fn (array $accounts) => $accounts[LedgerAccount::DepositsHeld->value] ?? 0),
                'upcoming_minor' => array_sum(array_column($rows, 'upcoming_minor')),
            ],
            'rentals' => $rows,
        ];
    }

    /**
     * HU-09.04: commissions, balances owed to owners, deposits held and overdue charges.
     *
     * @return array<string, mixed>
     */
    public function panel(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();

        $balance = fn (LedgerAccount $account, ?CarbonImmutable $since = null): int => (int) LedgerEntry::query()
            ->where('account', $account)
            ->where('direction', EntryDirection::Credit)
            ->when($since, fn ($query, $date) => $query->where('created_at', '>=', $date))
            ->sum('amount_minor');

        $owners = LedgerEntry::query()
            ->where('account', LedgerAccount::OwnerPayable)
            ->where('direction', EntryDirection::Credit)
            ->toBase()
            ->selectRaw('holder_id, sum(amount_minor) as total')
            ->groupBy('holder_id')
            ->orderByDesc('total')
            ->get();
        $names = User::query()->whereIn('id', $owners->pluck('holder_id'))->get()->keyBy('id');

        $overdue = Charge::query()
            ->where('status', ChargeStatus::Overdue)
            ->with(['rental.client', 'rental.property'])
            ->orderBy('due_on')
            ->limit(50)
            ->get();

        $payments = PaymentAttempt::query()
            ->whereIn('status', [PaymentAttemptStatus::Succeeded, PaymentAttemptStatus::Failed])
            ->with(['charge.rental', 'client'])
            ->latest('processed_at')
            ->limit(10)
            ->get();

        return [
            'totals' => [
                'commission_minor' => $balance(LedgerAccount::PlatformCommission),
                'commission_month_minor' => $balance(LedgerAccount::PlatformCommission, $today->startOfMonth()),
                'owner_payable_minor' => $balance(LedgerAccount::OwnerPayable),
                'deposits_minor' => $balance(LedgerAccount::DepositsHeld),
                'overdue_minor' => (int) $overdue->sum(fn (Charge $charge) => $charge->rent_minor + $charge->deposit_minor),
            ],
            'owners' => $owners->map(fn (object $row): array => [
                'id' => (int) $row->holder_id,
                'name' => $names->get($row->holder_id)->name ?? '—',
                'amount_minor' => (int) $row->total,
            ])->values()->all(),
            'overdue' => $overdue->map(fn (Charge $charge): array => [
                'id' => $charge->id,
                'number' => $charge->number,
                'rental_id' => $charge->rental_id,
                'folio' => $charge->rental->folio,
                'client' => $charge->rental->client->name,
                'property' => $charge->rental->property->title,
                'due_on' => $charge->due_on->toDateString(),
                'amount_minor' => $charge->rent_minor + $charge->deposit_minor,
            ])->values()->all(),
            'payments' => $payments->map(fn (PaymentAttempt $attempt): array => [
                'id' => $attempt->id,
                'rental_id' => $attempt->charge->rental_id,
                'folio' => $attempt->charge->rental->folio,
                'number' => $attempt->charge->number,
                'client' => $attempt->client->name,
                'amount_minor' => $attempt->amount_minor,
                'status' => $attempt->status->value,
                'status_label' => $attempt->status->label(),
                'processed_at' => $attempt->processed_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<int>  $rentalIds
     * @return array<int, array<string, int>> rental id => account => credited amount
     */
    private function creditsByRental(array $rentalIds): array
    {
        /** @var Collection<int, object{rental_id: int, account: string, total: int}> $rows */
        $rows = LedgerEntry::query()
            ->whereIn('rental_id', $rentalIds)
            ->where('direction', EntryDirection::Credit)
            ->toBase()
            ->selectRaw('rental_id, account, sum(amount_minor) as total')
            ->groupBy('rental_id', 'account')
            ->get();

        $credits = [];

        foreach ($rows as $row) {
            $credits[(int) $row->rental_id][(string) $row->account] = (int) $row->total;
        }

        return $credits;
    }

    /**
     * Rent still to be paid on confirmed and active rentals.
     *
     * @param  list<int>  $rentalIds
     * @return array<int, int>
     */
    private function upcomingRentByRental(array $rentalIds): array
    {
        return Charge::query()
            ->whereIn('rental_id', $rentalIds)
            ->whereIn('status', ChargeStatus::unpaid())
            ->whereHas('rental', fn (Builder $rental) => $rental->whereIn('status', [RentalStatus::Confirmed, RentalStatus::Active]))
            ->toBase()
            ->selectRaw('rental_id, sum(rent_minor) as total')
            ->groupBy('rental_id')
            ->pluck('total', 'rental_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
