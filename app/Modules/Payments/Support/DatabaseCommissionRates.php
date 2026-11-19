<?php

namespace App\Modules\Payments\Support;

use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;

final class DatabaseCommissionRates implements CommissionRates
{
    public function basisPointsFor(ManagementMode $mode, ?CarbonImmutable $on = null): int
    {
        $rule = CommissionRule::query()
            ->where('management_mode', $mode)
            ->validOn($on ?? CarbonImmutable::today())
            ->latest('valid_from')
            ->first();

        // A missing rule is a configuration error, never a silent 0 % commission.
        return $rule->percentage_bp
            ?? throw new BusinessRuleViolation('RN-50', Text::of('There is no commission rule in force for :mode.', ['mode' => $mode->label()]));
    }
}
