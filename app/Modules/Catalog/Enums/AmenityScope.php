<?php

namespace App\Modules\Catalog\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * Where an amenity can be offered: shared areas of the property, a specific unit, or both.
 */
enum AmenityScope: string
{
    use HasLabel;

    case Property = 'property';
    case Unit = 'unit';
    case Both = 'both';

    /**
     * @return list<self>
     */
    public static function forProperty(): array
    {
        return [self::Property, self::Both];
    }

    /**
     * @return list<self>
     */
    public static function forUnit(): array
    {
        return [self::Unit, self::Both];
    }
}
