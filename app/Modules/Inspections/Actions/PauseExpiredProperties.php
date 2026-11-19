<?php

namespace App\Modules\Inspections\Actions;

use App\Modules\Properties\Actions\PauseForExpiredInspection;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use Carbon\CarbonImmutable;

/**
 * RN-85: a published property whose inspection expired leaves the catalog. Runs daily.
 */
class PauseExpiredProperties
{
    public function __construct(private PauseForExpiredInspection $pause) {}

    public function handle(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $paused = 0;

        Property::query()
            ->where('status', PropertyStatus::Published)
            ->whereNotNull('inspection_valid_until')
            ->whereDate('inspection_valid_until', '<', $today)
            ->each(function (Property $property) use (&$paused) {
                $paused += $this->pause->handle($property) ? 1 : 0;
            });

        return $paused;
    }
}
