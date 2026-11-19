<?php

namespace App\Modules\Pricing\Models;

use App\Modules\Pricing\Enums\BlockReason;
use App\Modules\Properties\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dates a unit cannot be rented, [starts_on, ends_on) like rentals (RN-22, RN-26).
 *
 * @property int $id
 * @property int $unit_id
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property BlockReason $reason
 * @property string|null $note
 * @property int $created_by
 */
class AvailabilityBlock extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'reason' => BlockReason::class,
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
