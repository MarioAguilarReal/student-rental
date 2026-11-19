<?php

namespace App\Modules\Pricing\Presenters;

use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Pricing\Enums\BlockReason;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Models\Property;
use Carbon\CarbonImmutable;

/**
 * Prices, deposits and blocks of a property, shaped for the property editor. Read-only.
 */
class PricingPresenter
{
    public function __construct(private CommissionRates $commissionRates) {}

    /**
     * @return array<string, mixed>
     */
    public function forProperty(Property $property): array
    {
        $unitIds = $property->units()->pluck('id');
        $plans = RatePlan::query()->whereIn('unit_id', $unitIds)->get()->groupBy('unit_id');
        $deposits = UnitDeposit::query()->whereIn('unit_id', $unitIds)->pluck('amount_minor', 'unit_id');

        return [
            'units' => $unitIds->mapWithKeys(fn (int $unitId): array => [$unitId => [
                'deposit_minor' => $deposits[$unitId] ?? null,
                'plans' => collect(RatePeriod::cases())->mapWithKeys(function (RatePeriod $period) use ($plans, $unitId): array {
                    /** @var RatePlan|null $plan */
                    $plan = $plans->get($unitId)?->firstWhere('period', $period);

                    return [$period->value => $plan ? [
                        'enabled' => $plan->is_active,
                        'price_minor' => $plan->price_minor,
                        'min_periods' => $plan->min_periods,
                        'max_periods' => $plan->max_periods,
                    ] : null];
                }),
            ]]),
            'blocks' => AvailabilityBlock::query()
                ->whereIn('unit_id', $unitIds)
                ->whereDate('ends_on', '>=', CarbonImmutable::today())
                ->with('unit:id,name')
                ->orderBy('starts_on')
                ->get()
                ->map(fn (AvailabilityBlock $block): array => [
                    'id' => $block->id,
                    'unit_id' => $block->unit_id,
                    'unit_name' => $block->unit->name,
                    'starts_on' => $block->starts_on->toDateString(),
                    'ends_on' => $block->ends_on->toDateString(),
                    'reason' => $block->reason->label(),
                    'note' => $block->note,
                ]),
            'commission_bp' => $this->commissionRates->basisPointsFor($property->management_mode),
            'suggestions' => config('pricing.suggestions'),
            'periods' => array_map(fn (RatePeriod $period): array => [
                'value' => $period->value,
                'label' => $period->label(),
                'max_limit' => $period->maxPeriodsLimit(),
            ], RatePeriod::cases()),
            'blockReasons' => BlockReason::options(),
        ];
    }
}
