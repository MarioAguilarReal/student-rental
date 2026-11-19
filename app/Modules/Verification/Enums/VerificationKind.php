<?php

namespace App\Modules\Verification\Enums;

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * A user may hold one profile of each kind (e.g. a teacher who also owns a house).
 */
enum VerificationKind: string
{
    use HasLabel;

    case Client = 'client';
    case Owner = 'owner';

    public static function forRole(Role $role): ?self
    {
        return match ($role) {
            Role::Client => self::Client,
            Role::Owner => self::Owner,
            default => null,
        };
    }
}
