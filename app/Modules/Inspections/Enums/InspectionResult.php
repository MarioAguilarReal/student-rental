<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum InspectionResult: string
{
    use HasLabel;

    case Approved = 'approved';
    case ApprovedWithObservations = 'approved_with_observations';
    case Rejected = 'rejected';

    public function approves(): bool
    {
        return $this !== self::Rejected;
    }
}
