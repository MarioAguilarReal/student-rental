<?php

namespace App\Modules\Rentals\Models;

use App\Models\User;
use App\Modules\Payments\Models\Charge;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Shared\Money;
use Carbon\CarbonImmutable;
use Database\Factories\RentalFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A client's use of a unit for [starts_on, ends_on). Commercial values are a copy taken
 * when requested (RN-45), so later price or mode changes never affect it.
 *
 * @property int $id
 * @property string|null $folio
 * @property int $unit_id
 * @property int $property_id
 * @property int $client_id
 * @property RatePeriod $period
 * @property int $periods
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property int $price_minor
 * @property int $total_minor
 * @property int $deposit_minor
 * @property string $currency
 * @property ManagementMode $management_mode
 * @property int $commission_bp
 * @property RentalStatus $status
 * @property string|null $client_message
 * @property CarbonImmutable $rules_accepted_at
 * @property CarbonImmutable|null $expires_at
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $confirmed_at
 * @property int|null $cancelled_by
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property CarbonImmutable $created_at
 * @property-read Unit $unit
 * @property-read Property $property
 * @property-read User $client
 */
#[UseFactory(RentalFactory::class)]
class Rental extends Model
{
    /** @use HasFactory<RentalFactory> */
    use HasFactory;

    /** RN-61 */
    public const REQUEST_HOURS = 72;

    /** RN-62 */
    public const PAYMENT_HOURS = 48;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period' => RatePeriod::class,
            'management_mode' => ManagementMode::class,
            'status' => RentalStatus::class,
            'periods' => 'integer',
            'price_minor' => 'integer',
            'total_minor' => 'integer',
            'deposit_minor' => 'integer',
            'commission_bp' => 'integer',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'rules_accepted_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<Charge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class)->orderBy('number');
    }

    /**
     * RN-23: approved (until it expires), confirmed or active.
     *
     * @param  Builder<Rental>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->where(fn (Builder $blocking) => $blocking
            ->whereIn('status', [RentalStatus::Confirmed, RentalStatus::Active])
            ->orWhere(fn (Builder $approved) => $approved
                ->where('status', RentalStatus::Approved)
                ->where('expires_at', '>', now())));
    }

    /**
     * Rentals whose [starts_on, ends_on) overlaps [start, end).
     *
     * @param  Builder<Rental>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $query->whereDate('starts_on', '<', $end)->whereDate('ends_on', '>', $start);
    }

    public function total(): Money
    {
        return Money::ofMinor($this->total_minor, $this->currency);
    }

    public function deposit(): Money
    {
        return Money::ofMinor($this->deposit_minor, $this->currency);
    }
}
