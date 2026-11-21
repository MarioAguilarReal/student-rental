<?php

namespace App\Modules\Inspections\Actions;

use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Inspections\Events\InspectionRenewalOpened;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;
use Carbon\CarbonImmutable;

/**
 * HU-06.06 · RN-85: 30 days (HU-03.05) before an inspection expires, a renewal is opened and the owner
 * and the administrators are told (HU-10.01). Runs daily.
 */
class OpenRenewalInspections
{
    public function __construct(private OpenInspection $openInspection) {}

    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $opened = 0;

        Property::query()
            ->whereIn('status', [PropertyStatus::Approved, PropertyStatus::Published, PropertyStatus::Paused])
            ->whereNotNull('inspection_valid_until')
            ->whereDate('inspection_valid_until', '<=', $today->addDays(Parameters::int(SystemParameter::InspectionRenewalDays)))
            ->each(function (Property $property) use (&$opened) {
                $inspection = $this->openInspection->handle($property, InspectionType::Renewal);

                if ($inspection->wasRecentlyCreated) {
                    InspectionRenewalOpened::dispatch($inspection);
                    $opened++;
                }
            });

        return $opened;
    }
}
