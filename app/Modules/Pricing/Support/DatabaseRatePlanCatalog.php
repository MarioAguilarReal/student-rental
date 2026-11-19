<?php

namespace App\Modules\Pricing\Support;

use App\Modules\Pricing\Contracts\RatePlanCatalog;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Models\Property;

/**
 * RN-10: a property needs at least one active plan on a rentable, active space to be published.
 */
final class DatabaseRatePlanCatalog implements RatePlanCatalog
{
    public function hasActivePlan(Property $property): bool
    {
        return RatePlan::query()
            ->where('is_active', true)
            ->whereHas('unit', fn ($unit) => $unit
                ->where('property_id', $property->id)
                ->where('is_rentable', true)
                ->where('status', UnitStatus::Active))
            ->exists();
    }
}
