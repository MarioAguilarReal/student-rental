<?php

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Enums\ChargeConcept;
use App\Modules\Payments\Enums\ChargeStatus;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One payment the client owes for a rental (RN-70). Rent and deposit are kept apart (RN-73).
 *
 * @property int $id
 * @property int $rental_id
 * @property int $number
 * @property ChargeConcept $concept
 * @property int $rent_minor
 * @property int $deposit_minor
 * @property string $currency
 * @property CarbonImmutable $due_on
 * @property ChargeStatus $status
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $reminder_sent_at
 * @property-read Rental $rental
 * @property-read Collection<int, PaymentAttempt> $attempts
 */
class Charge extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'concept' => ChargeConcept::class,
            'status' => ChargeStatus::class,
            'number' => 'integer',
            'rent_minor' => 'integer',
            'deposit_minor' => 'integer',
            'due_on' => 'immutable_date',
            'paid_at' => 'immutable_datetime',
            'reminder_sent_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * @return HasMany<PaymentAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function isUnpaid(): bool
    {
        return in_array($this->status, ChargeStatus::unpaid(), true);
    }

    public function amount(): Money
    {
        return Money::ofMinor($this->rent_minor + $this->deposit_minor, $this->currency);
    }
}
