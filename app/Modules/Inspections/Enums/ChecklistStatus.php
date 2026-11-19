<?php

namespace App\Modules\Inspections\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-82: a published version is immutable; changes go into a new draft version.
 */
enum ChecklistStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';
}
