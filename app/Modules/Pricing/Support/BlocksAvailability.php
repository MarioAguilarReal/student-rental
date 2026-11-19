<?php

namespace App\Modules\Pricing\Support;

use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Properties\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class BlocksAvailability implements UnitAvailability
{
    public function isAvailable(Unit $unit, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return ! AvailabilityBlock::query()
            ->whereIn('unit_id', $this->family($unit))
            ->whereDate('starts_on', '<', $end)
            ->whereDate('ends_on', '>', $start)
            ->exists();
    }

    public function unavailableRanges(Unit $unit, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $ranges = AvailabilityBlock::query()
            ->whereIn('unit_id', $this->family($unit))
            ->whereDate('ends_on', '>', $from)
            ->whereDate('starts_on', '<', $until)
            ->orderBy('starts_on')
            ->get(['starts_on', 'ends_on'])
            ->map(fn (AvailabilityBlock $block): array => [$block->starts_on, $block->ends_on]);

        $merged = [];

        foreach ($ranges as [$start, $end]) {
            $last = array_key_last($merged);

            if ($last !== null && $start->lessThanOrEqualTo($merged[$last][1])) {
                $merged[$last][1] = $end->max($merged[$last][1]);
            } else {
                $merged[] = [$start, $end];
            }
        }

        return array_map(fn (array $range): array => ['starts_on' => $range[0]->toDateString(), 'ends_on' => $range[1]->toDateString()], $merged);
    }

    /**
     * @return Collection<int, int>
     */
    private function family(Unit $unit): Collection
    {
        return collect([$unit->id])->merge($unit->ancestors()->pluck('id'))->merge($unit->descendants()->pluck('id'));
    }
}
