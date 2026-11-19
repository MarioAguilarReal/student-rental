<?php

namespace Database\Factories;

use App\Modules\Catalog\Enums\AmenityCategory;
use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category' => AmenityCategory::Basics,
            'scope' => AmenityScope::Both,
            'is_active' => true,
        ];
    }

    public function scope(AmenityScope $scope): static
    {
        return $this->state(['scope' => $scope]);
    }
}
