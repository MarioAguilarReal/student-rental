<?php

namespace App\Modules\Rentals\Actions;

use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalCompleted;
use App\Modules\Rentals\Events\RentalStarted;
use App\Modules\Rentals\Models\Rental;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-08.06 · RN-65: Confirmed → Active on the start date; Active → Completed on the end date
 * (exclusive, so the stay is over that day). Runs daily and is idempotent.
 */
class AdvanceRentals
{
    /**
     * @return array{started: int, completed: int}
     */
    public function handle(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $counts = ['started' => 0, 'completed' => 0];

        Rental::query()->where('status', RentalStatus::Confirmed)->whereDate('starts_on', '<=', $today)->orderBy('id')
            ->each(function (Rental $rental) use (&$counts) {
                $counts['started'] += $this->move($rental, RentalStatus::Confirmed, RentalStatus::Active) ? 1 : 0;
            });

        Rental::query()->where('status', RentalStatus::Active)->whereDate('ends_on', '<=', $today)->orderBy('id')
            ->each(function (Rental $rental) use (&$counts) {
                $counts['completed'] += $this->move($rental, RentalStatus::Active, RentalStatus::Completed) ? 1 : 0;
            });

        return $counts;
    }

    private function move(Rental $rental, RentalStatus $from, RentalStatus $to): bool
    {
        return DB::transaction(function () use ($rental, $from, $to) {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);

            if ($rental->status !== $from) {
                return false;
            }

            $rental->status->assertCanTransitionTo($to);
            $rental->forceFill(['status' => $to])->save();

            $to === RentalStatus::Active ? RentalStarted::dispatch($rental) : RentalCompleted::dispatch($rental);

            return true;
        });
    }
}
