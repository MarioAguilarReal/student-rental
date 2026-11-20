<?php

use App\Modules\Payments\Enums\ChargeConcept;
use App\Modules\Payments\Support\ChargeSchedule;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Shared\Money;
use Carbon\CarbonImmutable;

/**
 * RN-70 · Calendario de cargos.
 */
$approvedOn = CarbonImmutable::parse('2027-01-10');
$start = CarbonImmutable::parse('2027-01-31');

it('charges a weekly stay at once, with the deposit', function () use ($approvedOn, $start) {
    $charges = ChargeSchedule::plan(RatePeriod::Weekly, 3, $start, Money::ofMinor(150_000), Money::ofMinor(100_000), $approvedOn);

    expect($charges)->toHaveCount(1)
        ->and($charges[0]['rent']->minorAmount())->toBe(450_000)
        ->and($charges[0]['deposit']->minorAmount())->toBe(100_000)
        ->and($charges[0]['concept'])->toBe(ChargeConcept::RentAndDeposit)
        ->and($charges[0]['due_on']->toDateString())->toBe('2027-01-10');
});

it('charges a monthly stay month by month, due when each month starts (RN-42)', function () use ($approvedOn, $start) {
    $charges = ChargeSchedule::plan(RatePeriod::Monthly, 3, $start, Money::ofMinor(450_000), Money::ofMinor(450_000), $approvedOn);

    expect(array_map(fn (array $charge) => $charge['due_on']->toDateString(), $charges))->toBe(['2027-01-10', '2027-02-28', '2027-03-31'])
        ->and(array_map(fn (array $charge) => $charge['rent']->plus($charge['deposit'])->minorAmount(), $charges))->toBe([900_000, 450_000, 450_000])
        ->and($charges[1]['concept'])->toBe(ChargeConcept::Rent);
});

it('splits a yearly price into 12 installments, the last one absorbing the rounding', function () use ($approvedOn, $start) {
    $charges = ChargeSchedule::plan(RatePeriod::Yearly, 1, $start, Money::ofMinor(4_860_001), Money::zero(), $approvedOn);

    expect($charges)->toHaveCount(12)
        ->and($charges[0]['rent']->minorAmount())->toBe(405_000)
        ->and($charges[11]['rent']->minorAmount())->toBe(405_001)
        ->and($charges[0]['concept'])->toBe(ChargeConcept::Rent)
        ->and(array_sum(array_map(fn (array $charge) => $charge['rent']->minorAmount(), $charges)))->toBe(4_860_001);
});
