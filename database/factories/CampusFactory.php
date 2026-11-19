<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Campus;
use App\Modules\Catalog\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    protected $model = Campus::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name' => 'Campus '.fake()->city(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(14, 32),
            'longitude' => fake()->longitude(-117, -86),
        ];
    }
}
