<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $slug = fake()->unique()->lexify('uni????');

        return [
            'name' => 'Universidad '.fake()->unique()->city(),
            'acronym' => strtoupper(substr($slug, 0, 6)),
            'email_domains' => ["{$slug}.edu.mx"],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
