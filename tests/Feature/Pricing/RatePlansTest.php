<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Actions\SuggestPrices;
use App\Modules\Pricing\Contracts\RatePlanCatalog;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;

/**
 * HU-05.01 · Planes de renta (RN-40, RN-41, RN-43) and HU-05.02 · Depósito (RN-44).
 */
beforeEach(function () {
    $this->owner = userWithRole(Role::Owner);
    $this->property = Property::factory()->create(['owner_id' => $this->owner->id]);
    $this->unit = Unit::factory()->create(['property_id' => $this->property->id]);
});

function pricingData(array $overrides = []): array
{
    return array_replace_recursive([
        'plans' => [
            'weekly' => ['enabled' => false, 'price' => null, 'min_periods' => null, 'max_periods' => null],
            'monthly' => ['enabled' => true, 'price' => '4500', 'min_periods' => 4, 'max_periods' => 6],
            'yearly' => ['enabled' => false, 'price' => null, 'min_periods' => null, 'max_periods' => null],
        ],
        'deposit' => '4500',
    ], $overrides);
}

function savePricing(array $data = [])
{
    return test()->actingAs(test()->owner)->put(route('pricing.units.update', [test()->property, test()->unit]), pricingData($data));
}

it('saves the plans in cents and the deposit', function () {
    savePricing(['plans' => ['weekly' => ['enabled' => true, 'price' => '1299.50', 'min_periods' => 1, 'max_periods' => 8]]])
        ->assertSessionHasNoErrors();

    $monthly = RatePlan::query()->where('unit_id', $this->unit->id)->where('period', RatePeriod::Monthly)->sole();
    $weekly = RatePlan::query()->where('unit_id', $this->unit->id)->where('period', RatePeriod::Weekly)->sole();

    expect($monthly->price_minor)->toBe(450_000)
        ->and([$monthly->min_periods, $monthly->max_periods])->toBe([4, 6])
        ->and($weekly->price_minor)->toBe(129_950)
        ->and(UnitDeposit::query()->where('unit_id', $this->unit->id)->value('amount_minor'))->toBe(450_000);
    $this->assertDatabaseHas('activity_log', ['event' => 'pricing_updated', 'subject_id' => $this->unit->id]);
});

it('keeps one plan per period and deactivates instead of deleting (RN-40)', function () {
    savePricing();
    savePricing(['plans' => ['monthly' => ['price' => '4800']]]);
    savePricing(['plans' => ['monthly' => ['enabled' => false]]]);

    $plans = RatePlan::query()->where('unit_id', $this->unit->id)->get();
    expect($plans)->toHaveCount(1)
        ->and($plans->first()?->is_active)->toBeFalse()
        ->and($plans->first()?->price_minor)->toBe(480_000);
});

it('validates the periods range (RN-43)', function (array $plan, string $field) {
    savePricing(['plans' => ['monthly' => $plan]])->assertSessionHasErrors($field);
})->with([
    'max below min' => [['min_periods' => 6, 'max_periods' => 4], 'plans.monthly.max_periods'],
    'max over the limit' => [['max_periods' => 25], 'plans.monthly.max_periods'],
    'no price' => [['price' => '0'], 'plans.monthly.price'],
    'missing price' => [['price' => null], 'plans.monthly.price'],
]);

it('rejects a negative deposit and accepts zero', function () {
    savePricing(['deposit' => '-1'])->assertSessionHasErrors('deposit');
    savePricing(['deposit' => '0'])->assertSessionHasNoErrors();
});

it('only prices spaces that are rented on their own', function () {
    $this->unit->update(['is_rentable' => false]);

    savePricing()->assertSessionHasErrors('domain');
    expect(RatePlan::query()->count())->toBe(0);
});

it('does not send the property back to inspection when prices change (RN-11)', function () {
    $this->property->update(['status' => PropertyStatus::Published, 'inspection_valid_until' => now()->addYear()]);

    savePricing()->assertSessionHasNoErrors();

    expect($this->property->fresh()?->status)->toBe(PropertyStatus::Published);
});

it('forbids pricing someone else\'s property', function () {
    $this->actingAs(userWithRole(Role::Owner))
        ->put(route('pricing.units.update', [$this->property, $this->unit]), pricingData())
        ->assertForbidden();
});

it('suggests weekly and yearly prices from the monthly one, rounded to $10 (RN-41)', function () {
    expect(app(SuggestPrices::class)->handle(450_000))->toBe(['weekly' => 129_000, 'yearly' => 4_860_000]);
});

it('counts as priced for publication only with an active plan on a rentable space (RN-10)', function () {
    $catalog = app(RatePlanCatalog::class);
    expect($catalog->hasActivePlan($this->property))->toBeFalse();

    savePricing();
    expect($catalog->hasActivePlan($this->property))->toBeTrue();

    $this->unit->update(['is_rentable' => false]);
    expect($catalog->hasActivePlan($this->property->fresh()))->toBeFalse();
});

it('sends the editor the prices, the commission in force and the suggestion factors', function () {
    savePricing();

    $this->actingAs($this->owner)->get(route('properties.edit', $this->property))
        ->assertInertia(fn ($page) => $page
            ->where('pricing.commission_bp', 1000)
            ->where("pricing.units.{$this->unit->id}.plans.monthly.price_minor", 450_000)
            ->where("pricing.units.{$this->unit->id}.deposit_minor", 450_000)
            ->has('pricing.periods', 3));
});
