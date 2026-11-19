<?php

namespace App\Modules\Inspections\Actions;

use App\Modules\Inspections\Enums\ItemScope;
use App\Modules\Inspections\Models\ChecklistItem;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Properties\Models\Unit;

/**
 * RN-87: what must be answered — every property item once, every unit item per rentable space.
 */
class RequiredAnswers
{
    /**
     * @return list<array{item: ChecklistItem, unit: Unit|null}>
     */
    public function handle(Inspection $inspection): array
    {
        $items = $inspection->template->items;
        $units = $inspection->property->units()->where('is_rentable', true)->get();
        $required = [];

        foreach ($items as $item) {
            if ($item->scope === ItemScope::Property) {
                $required[] = ['item' => $item, 'unit' => null];

                continue;
            }

            foreach ($units as $unit) {
                $required[] = ['item' => $item, 'unit' => $unit];
            }
        }

        return $required;
    }
}
