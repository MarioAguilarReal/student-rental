<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Events\ChargeOverdue;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Enums\RentalStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * RN-74: a later charge not paid by its due date becomes overdue, and the client and the
 * operator are notified. No penalties in the MVP (B-11). Only confirmed and active rentals:
 * an approved one expires instead (RN-62). Runs daily and is idempotent.
 */
class MarkOverdueCharges
{
    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $marked = 0;

        Charge::query()
            ->whereIn('status', [ChargeStatus::Pending, ChargeStatus::Failed])
            ->whereDate('due_on', '<', $today)
            ->whereHas('rental', fn (Builder $rental) => $rental->whereIn('status', [RentalStatus::Confirmed, RentalStatus::Active]))
            ->orderBy('id')
            ->each(function (Charge $charge) use (&$marked) {
                $marked += $this->mark($charge) ? 1 : 0;
            });

        return $marked;
    }

    private function mark(Charge $charge): bool
    {
        return DB::transaction(function () use ($charge) {
            $charge = Charge::query()->lockForUpdate()->findOrFail($charge->id);

            if (! in_array($charge->status, [ChargeStatus::Pending, ChargeStatus::Failed], true)) {
                return false;
            }

            $charge->status->assertCanTransitionTo(ChargeStatus::Overdue);
            $charge->forceFill(['status' => ChargeStatus::Overdue])->save();

            ChargeOverdue::dispatch($charge);

            return true;
        });
    }
}
