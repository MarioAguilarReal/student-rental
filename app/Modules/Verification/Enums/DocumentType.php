<?php

namespace App\Modules\Verification\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

enum DocumentType: string
{
    use HasLabel;

    case Credential = 'credential';
    case AcceptanceLetter = 'acceptance_letter';
    case OfficialId = 'official_id';
}
