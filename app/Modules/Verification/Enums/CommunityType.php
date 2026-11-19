<?php

namespace App\Modules\Verification\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum CommunityType: string
{
    use HasLabel;

    case Faculty = 'faculty';
    case Student = 'student';
    case ExchangeStudent = 'exchange_student';

    /**
     * RN-04: how long an approved verification lasts. Exchange students holding an
     * acceptance letter are capped by their stay instead (see ApproveVerification).
     */
    public function validityMonths(): int
    {
        return match ($this) {
            self::Faculty => 12,
            self::Student, self::ExchangeStudent => 6,
        };
    }
}
