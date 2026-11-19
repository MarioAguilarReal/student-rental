<?php

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RN-44: one deposit amount per unit (it may be 0).
 *
 * @property int $id
 * @property int $unit_id
 * @property int $amount_minor
 * @property string $currency
 */
class UnitDeposit extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer'];
    }
}
