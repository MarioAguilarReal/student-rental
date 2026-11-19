<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\UnitTemplate;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Facades\DB;

/**
 * HU-04.02: starting layouts — whole home only, rooms only, or both.
 */
class ApplyUnitTemplate
{
    public function __construct(private MarkForReinspection $markForReinspection) {}

    public function handle(Property $property, UnitTemplate $template, int $rooms, int $capacity): void
    {
        EnsurePropertyIsEditable::check($property);

        if ($property->units()->exists()) {
            throw new BusinessRuleViolation('HU-04.02', __('This property already has spaces. Edit them instead.'), 'template');
        }

        if ($template !== UnitTemplate::EntirePlace && ($rooms < 1 || $rooms > 20)) {
            throw new BusinessRuleViolation('HU-04.02', __('Indicate between 1 and 20 rooms.'), 'rooms');
        }

        DB::transaction(function () use ($property, $template, $rooms, $capacity) {
            $root = Unit::create([
                'property_id' => $property->id,
                'type' => UnitType::EntirePlace,
                'name' => __('Entire place'),
                'capacity' => $template === UnitTemplate::EntirePlace ? max(1, $capacity) : max($rooms, $capacity),
                'is_rentable' => $template !== UnitTemplate::ByRooms,
                'position' => 0,
            ]);

            if ($template !== UnitTemplate::EntirePlace) {
                foreach (range(1, $rooms) as $number) {
                    Unit::create([
                        'property_id' => $property->id,
                        'parent_id' => $root->id,
                        'type' => UnitType::PrivateRoom,
                        'name' => __('Room :number', ['number' => $number]),
                        'capacity' => 1,
                        'is_rentable' => true,
                        'position' => $number,
                    ]);
                }
            }

            $this->markForReinspection->handle($property);
        });
    }
}
