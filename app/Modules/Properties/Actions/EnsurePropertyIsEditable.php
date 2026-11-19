<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;

/**
 * RN-13: archived properties are read-only.
 */
class EnsurePropertyIsEditable
{
    public static function check(Property $property): void
    {
        (new self)->handle($property);
    }

    public function handle(Property $property): void
    {
        if ($property->status === PropertyStatus::Archived) {
            throw new BusinessRuleViolation('RN-13', __('Archived properties cannot be changed.'));
        }
    }
}
