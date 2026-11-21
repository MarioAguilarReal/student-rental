<?php

namespace App\Modules\Audit\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-90: the areas the audit trail groups its records by (activity log names).
 */
enum AuditLog: string
{
    use HasLabel;

    case Identity = 'identity';
    case Verification = 'verification';
    case Property = 'property';
    case Pricing = 'pricing';
    case Inspection = 'inspection';
    case Rental = 'rental';
    case Payment = 'payment';
    case Settings = 'settings';
}
