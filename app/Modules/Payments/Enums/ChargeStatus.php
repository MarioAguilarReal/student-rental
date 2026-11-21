<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\Concerns\HasTransitions;

/**
 * A scheduled charge (RN-70). It is paid by the client (HU-09.01), fails when the gateway
 * declines (and can be retried), goes overdue after its due date (RN-74) and is cancelled
 * when its rental expires or is cancelled (RN-62, RN-66).
 */
enum ChargeStatus: string
{
    use HasLabel, HasTransitions;

    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Failed, self::Overdue, self::Cancelled],
            self::Failed => [self::Paid, self::Overdue, self::Cancelled],
            self::Overdue => [self::Paid, self::Cancelled],
            self::Paid, self::Cancelled => [],
        };
    }

    /**
     * @return list<self>
     */
    public static function unpaid(): array
    {
        return [self::Pending, self::Failed, self::Overdue];
    }
}
