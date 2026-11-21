<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\Concerns\HasTransitions;

/**
 * One try at paying a charge (HU-09.01). Processing until the gateway answers.
 */
enum PaymentAttemptStatus: string
{
    use HasLabel, HasTransitions;

    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Processing => [self::Succeeded, self::Failed],
            self::Succeeded, self::Failed => [],
        };
    }
}
