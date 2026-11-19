<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\HouseRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseRule>
 */
class HouseRuleFactory extends Factory
{
    protected $model = HouseRule::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'description' => null, 'is_active' => true];
    }
}
