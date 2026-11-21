<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Events\CommissionRuleRemoved;
use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-03.04: a rate that has not started yet can be withdrawn; the previous one stays open.
 * Rates already in force or past are history and never change.
 */
class RemoveScheduledCommissionRule
{
    public function handle(User $actor, CommissionRule $rule): void
    {
        DB::transaction(function () use ($actor, $rule) {
            $rules = CommissionRule::query()->where('management_mode', $rule->management_mode)->lockForUpdate()->orderByDesc('valid_from')->get();
            $rule = $rules->firstWhere('id', $rule->id) ?? $rule;

            if (! $rule->valid_from->gt(CarbonImmutable::today())) {
                throw new BusinessRuleViolation('HU-03.04', Text::of('Only rates that have not started yet can be removed.'));
            }

            if ($rules->first()?->id !== $rule->id) {
                throw new BusinessRuleViolation('HU-03.04', Text::of('Remove the later rates first.'));
            }

            $rules->get(1)?->forceFill(['valid_until' => null])->save();
            $rule->delete();

            CommissionRuleRemoved::dispatch($rule, $actor);
        });
    }
}
