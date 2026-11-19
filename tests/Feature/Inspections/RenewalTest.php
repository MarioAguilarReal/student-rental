<?php

use App\Modules\Inspections\Actions\OpenRenewalInspections;
use App\Modules\Inspections\Actions\PauseExpiredProperties;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Properties\Enums\PauseReason;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;

/**
 * HU-06.06 · Renovación (RN-85).
 */
it('opens a renewal 30 days before the inspection expires, once', function () {
    $soon = Property::factory()->inspected('+30 days')->create(['status' => PropertyStatus::Published]);
    Property::factory()->inspected('+31 days')->create(['status' => PropertyStatus::Published]);

    expect(app(OpenRenewalInspections::class)->handle())->toBe(1)
        ->and(app(OpenRenewalInspections::class)->handle())->toBe(0);

    expect(Inspection::query()->sole()->property_id)->toBe($soon->id)
        ->and(Inspection::query()->sole()->type)->toBe(InspectionType::Renewal);
});

it('pauses published properties whose inspection expired', function () {
    $expired = Property::factory()->inspected('-1 day')->create(['status' => PropertyStatus::Published]);
    $current = Property::factory()->inspected('+1 day')->create(['status' => PropertyStatus::Published]);

    expect(app(PauseExpiredProperties::class)->handle())->toBe(1);

    expect($expired->fresh()?->status)->toBe(PropertyStatus::Paused)
        ->and($expired->fresh()?->pause_reason)->toBe(PauseReason::InspectionExpired)
        ->and($current->fresh()?->status)->toBe(PropertyStatus::Published);
});

it('schedules both daily jobs', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('inspections:open-renewals')
        ->expectsOutputToContain('inspections:pause-expired');
});
