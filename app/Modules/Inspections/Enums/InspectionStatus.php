<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\Concerns\HasTransitions;

/**
 * State machine in docs/spec/04-modelo-dominio.md ("Inspección").
 */
enum InspectionStatus: string
{
    use HasLabel, HasTransitions;

    case PendingAssignment = 'pending_assignment';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingAssignment => [self::Assigned, self::Cancelled],
            self::Assigned => [self::Assigned, self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::PendingAssignment, self::Assigned, self::InProgress], true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::PendingAssignment, self::Assigned, self::InProgress];
    }
}
