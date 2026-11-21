<?php

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Enums\EntryDirection;
use App\Modules\Payments\Enums\LedgerAccount;
use App\Modules\Rentals\Models\Rental;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RN-73 / RN-75: one immutable line of the double-entry ledger. The lines of a transaction
 * add up to zero; PostgreSQL refuses updates and deletes.
 *
 * @property int $id
 * @property string $transaction_id
 * @property LedgerAccount $account
 * @property int|null $holder_id
 * @property int $rental_id
 * @property int|null $charge_id
 * @property EntryDirection $direction
 * @property int $amount_minor
 * @property string $currency
 * @property CarbonImmutable $created_at
 * @property-read Rental $rental
 */
class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account' => LedgerAccount::class,
            'direction' => EntryDirection::class,
            'amount_minor' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
