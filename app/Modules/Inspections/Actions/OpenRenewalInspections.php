<?php

namespace App\Modules\Inspections\Actions;

use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use Carbon\CarbonImmutable;

/**
 * HU-06.06 · RN-85: 30 days before an inspection expires, a renewal is opened. Runs daily.
 */
class OpenRenewalInspections
{
    public const DAYS_BEFORE = 30;

    public function __construct(private OpenInspection $openInspection) {}

    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $opened = 0;

        Property::query()
            ->whereIn('status', [PropertyStatus::Approved, PropertyStatus::Published, PropertyStatus::Paused])
            ->whereNotNull('inspection_valid_until')
            ->whereDate('inspection_valid_until', '<=', $today->addDays(self::DAYS_BEFORE))
            ->each(function (Property $property) use (&$opened) {
                $inspection = $this->openInspection->handle($property, InspectionType::Renewal);
                $opened += $inspection->wasRecentlyCreated ? 1 : 0;
            });

        return $opened;
    }
}
