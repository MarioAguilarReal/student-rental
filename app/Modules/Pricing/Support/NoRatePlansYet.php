<?php

namespace App\Modules\Pricing\Support;

use App\Modules\Pricing\Contracts\RatePlanCatalog;
use App\Modules\Properties\Models\Property;

/**
 * Placeholder until rate plans exist (Iteración 3). No property can be published without
 * prices (RN-10), so it truthfully answers "no".
 */
final class NoRatePlansYet implements RatePlanCatalog
{
    public function hasActivePlan(Property $property): bool
    {
        return false;
    }
}
