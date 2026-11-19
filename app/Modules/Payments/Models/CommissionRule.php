<?php

namespace App\Modules\Payments\Models;

use App\Modules\Properties\Enums\ManagementMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * RN-50: commission by management mode, with validity so rates can change without code.
 *
 * @property int $id
 * @property ManagementMode $management_mode
 * @property int $percentage_bp
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable|null $valid_until
 */
class CommissionRule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'management_mode' => ManagementMode::class,
            'percentage_bp' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeValidOn(Builder $query, CarbonImmutable $date): void
    {
        $query->whereDate('valid_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date));
    }
}
