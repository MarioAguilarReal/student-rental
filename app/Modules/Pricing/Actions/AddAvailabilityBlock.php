<?php

namespace App\Modules\Pricing\Actions;

use App\Models\User;
use App\Modules\Pricing\Enums\BlockReason;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Properties\Actions\EnsurePropertyIsEditable;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Contracts\RentalOccupancy;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;

/**
 * HU-05.03 · RN-22 / RN-26: blocks dates on a unit, which also blocks its parent and its
 * subdivisions. A block cannot overlap another block of the same family, nor dates with
 * blocking rentals (RN-26).
 */
class AddAvailabilityBlock
{
    public const MAX_DAYS_AHEAD = 730;

    public function __construct(private RentalOccupancy $rentals) {}

    public function handle(User $actor, Unit $unit, CarbonImmutable $startsOn, CarbonImmutable $endsOn, BlockReason $reason, ?string $note): AvailabilityBlock
    {
        EnsurePropertyIsEditable::check($unit->property);

        if ($endsOn->lessThanOrEqualTo($startsOn)) {
            throw new BusinessRuleViolation('RN-26', Text::of('The end date must be after the start date.'), 'ends_on');
        }

        if ($startsOn->lessThan(CarbonImmutable::today()) || $endsOn->greaterThan(CarbonImmutable::today()->addDays(self::MAX_DAYS_AHEAD))) {
            throw new BusinessRuleViolation('RN-26', Text::of('Blocks must start today or later and end within two years.'), 'starts_on');
        }

        $family = collect([$unit->id])
            ->merge($unit->ancestors()->pluck('id'))
            ->merge($unit->descendants()->pluck('id'));

        $overlaps = AvailabilityBlock::query()
            ->whereIn('unit_id', $family)
            ->whereDate('starts_on', '<', $endsOn)
            ->whereDate('ends_on', '>', $startsOn)
            ->exists();

        if ($overlaps) {
            throw new BusinessRuleViolation('RN-22', Text::of('Those dates overlap another block of this space or of the space it belongs to.'), 'starts_on');
        }

        if ($this->rentals->isOccupied($unit, $startsOn, $endsOn)) {
            throw new BusinessRuleViolation('RN-26', Text::of('Those dates are already rented.'), 'starts_on');
        }

        return AvailabilityBlock::create([
            'unit_id' => $unit->id,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'reason' => $reason,
            'note' => $note,
            'created_by' => $actor->id,
        ]);
    }
}
