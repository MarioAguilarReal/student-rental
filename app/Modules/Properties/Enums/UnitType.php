<?php

namespace App\Modules\Properties\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * RN-20: allowed unit hierarchy (max. depth 3: entire place → shared room → bed).
 */
enum UnitType: string
{
    use HasLabel;

    case EntirePlace = 'entire_place';
    case PrivateRoom = 'private_room';
    case SharedRoom = 'shared_room';
    case Bed = 'bed';

    public function canBeRoot(): bool
    {
        return $this !== self::Bed;
    }

    /**
     * @return list<self>
     */
    public function allowedChildren(): array
    {
        return match ($this) {
            self::EntirePlace => [self::PrivateRoom, self::SharedRoom],
            self::SharedRoom => [self::Bed],
            self::PrivateRoom, self::Bed => [],
        };
    }
}
