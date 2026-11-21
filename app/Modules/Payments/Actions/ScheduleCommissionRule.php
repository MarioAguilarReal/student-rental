<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Events\CommissionRuleScheduled;
use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-03.04 · RN-50: the Super Admin sets a new rate for a management mode from a date on.
 *
 * - It starts today at the earliest: past rates are never rewritten (rentals keep their own
 *   copy anyway, RN-45).
 * - It starts after the latest rate of that mode, which now ends the day before, so two rules
 *   never overlap.
 */
class ScheduleCommissionRule
{
    public function handle(User $actor, ManagementMode $mode, int $basisPoints, CarbonImmutable $validFrom): CommissionRule
    {
        $validFrom = $validFrom->startOfDay();

        if ($basisPoints < 0 || $basisPoints > 10_000) {
            throw new BusinessRuleViolation('RN-50', Text::of('The commission must be between 0 % and 100 %.'), 'percentage');
        }

        if ($validFrom->lt(CarbonImmutable::today())) {
            throw new BusinessRuleViolation('HU-03.04', Text::of('A new rate can start today at the earliest.'), 'valid_from');
        }

        return DB::transaction(function () use ($actor, $mode, $basisPoints, $validFrom) {
            $latest = CommissionRule::query()->where('management_mode', $mode)->lockForUpdate()->orderByDesc('valid_from')->first();

            if ($latest !== null && $validFrom->lte($latest->valid_from)) {
                throw new BusinessRuleViolation('HU-03.04', Text::of('It must start after :date, when the latest rate starts.', [
                    'date' => $latest->valid_from->toDateString(),
                ]), 'valid_from');
            }

            $latest?->forceFill(['valid_until' => $validFrom->subDay()])->save();

            $rule = CommissionRule::create([
                'management_mode' => $mode,
                'percentage_bp' => $basisPoints,
                'valid_from' => $validFrom,
                'valid_until' => null,
            ]);

            CommissionRuleScheduled::dispatch($rule, $actor);

            return $rule;
        });
    }
}
