<?php

namespace Database\Factories;

use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'type' => UnitType::EntirePlace,
            'name' => 'Casa completa',
            'capacity' => 4,
            'is_rentable' => true,
            'status' => UnitStatus::Active,
        ];
    }

    public function childOf(Unit $parent, UnitType $type): static
    {
        return $this->state([
            'property_id' => $parent->property_id,
            'parent_id' => $parent->id,
            'type' => $type,
            'name' => $type->value.' '.fake()->numberBetween(1, 99),
            'capacity' => 1,
        ]);
    }
}
