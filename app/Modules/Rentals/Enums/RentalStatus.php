<?php

namespace App\Modules\Rentals\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\Concerns\HasTransitions;

/**
 * State machine in docs/spec/04-modelo-dominio.md ("Renta").
 */
enum RentalStatus: string
{
    use HasLabel, HasTransitions;

    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected, self::Expired],
            self::Approved => [self::Confirmed, self::Expired, self::Cancelled],
            self::Confirmed => [self::Active, self::Cancelled],
            self::Active => [self::Completed, self::Cancelled],
            self::Rejected, self::Expired, self::Completed, self::Cancelled => [],
        };
    }

    /**
     * RN-23: states that hold the dates (Approved only until expires_at, see Rental::scopeBlocking).
     *
     * @return list<self>
     */
    public static function blocking(): array
    {
        return [self::Approved, self::Confirmed, self::Active];
    }

    /**
     * HU-08.04: once confirmed the client sees the exact address and the operator's contact.
     */
    public function revealsAddress(): bool
    {
        return in_array($this, [self::Confirmed, self::Active, self::Completed], true);
    }

    public function isClosed(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
