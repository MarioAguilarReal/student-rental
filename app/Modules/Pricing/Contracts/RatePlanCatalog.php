<?php

namespace App\Modules\Pricing\Contracts;

use App\Modules\Properties\Models\Property;

/**
 * What the Properties module needs to know about prices to publish (RN-10).
 */
interface RatePlanCatalog
{
    public function hasActivePlan(Property $property): bool;
}
