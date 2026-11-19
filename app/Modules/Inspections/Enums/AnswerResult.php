<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum AnswerResult: string
{
    use HasLabel;

    case Compliant = 'compliant';
    case NonCompliant = 'non_compliant';
    case NotApplicable = 'not_applicable';
}
