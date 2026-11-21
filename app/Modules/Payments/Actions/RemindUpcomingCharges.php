<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Payments\Events\ChargeDueSoon;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-10.01: reminds the client of a charge due soon, once per charge. Only for confirmed
 * and active rentals; the first charge has its own 48 h deadline (RN-62). Runs daily.
 */
class RemindUpcomingCharges
{
    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $reminded = 0;

        Charge::query()
            ->whereIn('status', [ChargeStatus::Pending, ChargeStatus::Failed])
            ->whereNull('reminder_sent_at')
            ->whereDate('due_on', '>', $today)
            ->whereDate('due_on', '<=', $today->addDays(Parameters::int(SystemParameter::ChargeReminderDays)))
            ->whereHas('rental', fn (Builder $rental) => $rental->whereIn('status', [RentalStatus::Confirmed, RentalStatus::Active]))
            ->orderBy('id')
            ->each(function (Charge $charge) use (&$reminded) {
                $charge->forceFill(['reminder_sent_at' => now()])->save();

                ChargeDueSoon::dispatch($charge);
                $reminded++;
            });

        return $reminded;
    }
}
