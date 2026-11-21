<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Enums\SimulatedOutcome;
use App\Modules\Payments\Events\PaymentSimulationConfigured;
use App\Modules\Payments\Models\PaymentSimulationSetting;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-09.05 · RN-72: the Super Admin chooses what the simulated gateway answers to every
 * payment from now on: approve (default), decline, or answer after a delay.
 */
class ConfigurePaymentSimulation
{
    public const MAX_DELAY_SECONDS = 3600;

    public function handle(User $actor, SimulatedOutcome $outcome, int $delaySeconds): PaymentSimulationSetting
    {
        if ($delaySeconds < 1 || $delaySeconds > self::MAX_DELAY_SECONDS) {
            throw new BusinessRuleViolation('RN-72', Text::of('The delay must be between 1 second and 1 hour.'), 'delay_seconds');
        }

        return DB::transaction(function () use ($actor, $outcome, $delaySeconds) {
            $setting = PaymentSimulationSetting::query()->lockForUpdate()->first() ?? new PaymentSimulationSetting;
            $setting->forceFill(['outcome' => $outcome, 'delay_seconds' => $delaySeconds, 'updated_by' => $actor->id])->save();

            PaymentSimulationConfigured::dispatch($setting, $actor);

            return $setting;
        });
    }
}
