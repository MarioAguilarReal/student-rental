<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * HU-04.02: removing a unit removes its subdivisions too, and is critical (RN-11).
 * TODO(Iteración 6): refuse when the unit or its subdivisions have blocking rentals.
 */
class RemoveUnit
{
    public function __construct(private MarkForReinspection $markForReinspection) {}

    public function handle(Unit $unit): void
    {
        $property = $unit->property;
        EnsurePropertyIsEditable::check($property);

        DB::transaction(function () use ($unit, $property) {
            $unit->delete();
            $this->markForReinspection->handle($property);
        });
    }
}
