<?php

use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Carbon\CarbonImmutable;

/**
 * HU-03.04 (lectura) · RN-50: 10 % self-managed, 20 % managed, configurable with validity.
 */
it('returns the seeded rates', function () {
    $rates = app(CommissionRates::class);

    expect($rates->basisPointsFor(ManagementMode::SelfManaged))->toBe(1000)
        ->and($rates->basisPointsFor(ManagementMode::Managed))->toBe(2000);
});

it('uses the rule in force on the given date', function () {
    CommissionRule::query()->where('management_mode', ManagementMode::SelfManaged)->update(['valid_until' => '2027-03-31']);
    CommissionRule::create(['management_mode' => ManagementMode::SelfManaged, 'percentage_bp' => 1200, 'valid_from' => '2027-04-01']);

    $rates = app(CommissionRates::class);

    expect($rates->basisPointsFor(ManagementMode::SelfManaged, CarbonImmutable::parse('2027-03-31')))->toBe(1000)
        ->and($rates->basisPointsFor(ManagementMode::SelfManaged, CarbonImmutable::parse('2027-04-01')))->toBe(1200);
});

it('never falls back to a silent 0 % commission', function () {
    CommissionRule::query()->delete();

    app(CommissionRates::class)->basisPointsFor(ManagementMode::Managed);
})->throws(BusinessRuleViolation::class);
