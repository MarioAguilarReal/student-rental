<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\Concerns\HasTransitions;

/**
 * State machine in docs/spec/04-modelo-dominio.md ("Propiedad").
 */
enum PropertyStatus: string
{
    use HasLabel, HasTransitions;

    case Draft = 'draft';
    case PendingInspection = 'pending_inspection';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Paused = 'paused';
    case Archived = 'archived';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingInspection, self::Archived],
            self::PendingInspection => [self::Approved, self::Rejected],
            self::Rejected => [self::PendingInspection, self::Archived],
            self::Approved => [self::Published, self::PendingInspection, self::Archived],
            self::Published => [self::Paused, self::PendingInspection],
            self::Paused => [self::Published, self::PendingInspection, self::Archived],
            self::Archived => [],
        };
    }

    /**
     * RN-11: these properties already passed an inspection, so a critical change sends them back.
     */
    public function hasPassedInspection(): bool
    {
        return in_array($this, [self::Approved, self::Published, self::Paused], true);
    }
}
