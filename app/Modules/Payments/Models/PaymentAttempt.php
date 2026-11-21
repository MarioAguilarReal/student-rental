<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HU-09.01: one try at paying a charge through the gateway (D-05).
 *
 * @property int $id
 * @property int $charge_id
 * @property int $client_id
 * @property string $gateway
 * @property string|null $reference
 * @property int $amount_minor
 * @property string $currency
 * @property PaymentAttemptStatus $status
 * @property array<string, mixed>|null $response
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable $created_at
 * @property-read Charge $charge
 * @property-read User $client
 */
class PaymentAttempt extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PaymentAttemptStatus::class,
            'amount_minor' => 'integer',
            'response' => 'array',
            'processed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * The key the gateway echoes back so a late answer finds its attempt.
     */
    public function key(): string
    {
        return 'PA-'.$this->id;
    }

    /**
     * @return BelongsTo<Charge, $this>
     */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
