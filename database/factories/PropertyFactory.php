<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'created_by' => fn (array $attributes) => $attributes['owner_id'],
            'management_mode' => ManagementMode::SelfManaged,
            'title' => 'Casa '.fake()->streetName(),
            'description' => fake()->paragraph(),
            'property_type' => PropertyType::House,
            'status' => PropertyStatus::Draft,
        ];
    }

    public function withAddress(): static
    {
        return $this->state([
            'street' => 'Calle '.fake()->lastName(),
            'exterior_number' => (string) fake()->numberBetween(1, 9999),
            'neighborhood' => 'Colonia Centro',
            'city' => 'Tepic',
            'state' => 'Nayarit',
            'postal_code' => '63000',
            'latitude' => 21.5095,
            'longitude' => -104.8946,
        ]);
    }

    public function status(PropertyStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function inspected(string $until = '+1 year'): static
    {
        return $this->withAddress()->state([
            'status' => PropertyStatus::Approved,
            'inspection_valid_until' => now()->modify($until)->toDateString(),
        ]);
    }
}
