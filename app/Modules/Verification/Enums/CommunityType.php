<?php

namespace App\Modules\Verification\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;

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
            self::Faculty => Parameters::int(SystemParameter::VerificationFacultyMonths),
            self::Student, self::ExchangeStudent => Parameters::int(SystemParameter::VerificationStudentMonths),
        };
    }
}
