<?php

namespace App\Modules\Properties\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;

/**
 * HU-04.01: a property starts as a draft. RN-15: an administrator may create it on behalf of an owner.
 */
class CreateProperty
{
    public function handle(User $actor, User $owner, string $title, PropertyType $type, ManagementMode $mode): Property
    {
        if (! $owner->hasRole(Role::Owner->value)) {
            throw new BusinessRuleViolation('RN-15', __('The selected user is not an owner.'), 'owner_email');
        }

        return Property::create([
            'owner_id' => $owner->id,
            'created_by' => $actor->id,
            'title' => $title,
            'property_type' => $type,
            'management_mode' => $mode,
            'status' => PropertyStatus::Draft,
        ]);
    }
}
