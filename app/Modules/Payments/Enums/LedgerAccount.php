<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-73: the accounts of the double-entry ledger.
 */
enum LedgerAccount: string
{
    use HasLabel;

    /** What the client paid (holder: the client). */
    case Client = 'client';

    /** The platform's income (RN-50, RN-51). */
    case PlatformCommission = 'platform_commission';

    /** Rent minus commission, owed to the owner until payouts exist (holder: the owner, B-03). */
    case OwnerPayable = 'owner_payable';

    /** Deposits held in guarantee (holder: the client who paid them). */
    case DepositsHeld = 'deposits_held';
}
