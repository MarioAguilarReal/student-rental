<?php

namespace App\Modules\Pricing\Models;

use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $unit_id
 * @property RatePeriod $period
 * @property int $price_minor
 * @property string $currency
 * @property int $min_periods
 * @property int $max_periods
 * @property bool $is_active
 */
class RatePlan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period' => RatePeriod::class,
            'price_minor' => 'integer',
            'min_periods' => 'integer',
            'max_periods' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function price(): Money
    {
        return Money::ofMinor($this->price_minor, $this->currency);
    }
}
