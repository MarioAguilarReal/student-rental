<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum ChargeConcept: string
{
    use HasLabel;

    case Rent = 'rent';
    case RentAndDeposit = 'rent_and_deposit';
}
