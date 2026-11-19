<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Models\Property;

/**
 * HU-04.05: non-critical data, applied immediately. RN-14: a new management mode only
 * affects future rentals (rentals keep their own copy).
 */
class UpdatePropertyDetails
{
    public function handle(Property $property, string $title, ?string $description, PropertyType $type, ManagementMode $mode): Property
    {
        EnsurePropertyIsEditable::check($property);

        $property->update([
            'title' => $title,
            'description' => $description,
            'property_type' => $type,
            'management_mode' => $mode,
        ]);

        return $property;
    }
}
