<?php

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Enums\SimulatedOutcome;
use Illuminate\Database\Eloquent\Model;

/**
 * HU-09.05 · RN-72: the scenario the simulated gateway plays. A single row; without it the
 * gateway approves every payment.
 *
 * @property int $id
 * @property SimulatedOutcome $outcome
 * @property int $delay_seconds
 * @property int|null $updated_by
 */
class PaymentSimulationSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'outcome' => SimulatedOutcome::class,
            'delay_seconds' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->first() ?? new self(['outcome' => SimulatedOutcome::Approve, 'delay_seconds' => 30]);
    }
}
