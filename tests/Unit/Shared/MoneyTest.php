<?php

use App\Modules\Shared\Money;
use Brick\Money\Exception\MoneyMismatchException;

it('stores amounts in minor units with MXN as default currency', function () {
    $money = Money::ofMinor(500_000);

    expect($money->minorAmount())->toBe(500_000)
        ->and($money->currency())->toBe('MXN')
        ->and($money->jsonSerialize())->toBe(['amount' => 500_000, 'currency' => 'MXN']);
});

it('adds, subtracts and multiplies', function () {
    $money = Money::ofMinor(1_000)->plus(Money::ofMinor(250))->minus(Money::ofMinor(50))->multipliedBy(3);

    expect($money->minorAmount())->toBe(3_600);
});

it('refuses to mix currencies', function () {
    Money::ofMinor(100)->plus(Money::ofMinor(100, 'USD'));
})->throws(MoneyMismatchException::class);

it('calculates commissions from basis points', function (int $amount, int $basisPoints, int $expected) {
    expect(Money::ofMinor($amount)->percentage($basisPoints)->minorAmount())->toBe($expected);
})->with([
    'self-managed 10 % of $5,000' => [500_000, 1_000, 50_000],
    'managed 20 % of $5,000' => [500_000, 2_000, 100_000],
    'rounds half up' => [105, 1_000, 11],
    'rounds down below half' => [104, 1_000, 10],
]);

it('splits into installments with the remainder in the last one (RN-70)', function () {
    $parts = Money::ofMinor(100_000)->split(3);

    expect(array_map(fn (Money $part): int => $part->minorAmount(), $parts))->toBe([33_333, 33_333, 33_334]);
});

it('cannot split into zero parts', function () {
    Money::ofMinor(100)->split(0);
})->throws(InvalidArgumentException::class);

it('compares values', function () {
    expect(Money::ofMinor(100)->equals(Money::ofMinor(100)))->toBeTrue()
        ->and(Money::ofMinor(100)->equals(Money::ofMinor(100, 'USD')))->toBeFalse()
        ->and(Money::ofMinor(200)->greaterThan(Money::ofMinor(100)))->toBeTrue()
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and(Money::ofMinor(-1)->isNegative())->toBeTrue();
});
