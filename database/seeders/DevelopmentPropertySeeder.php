<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Properties\Actions\ApplyUnitTemplate;
use App\Modules\Properties\Actions\CreateProperty;
use App\Modules\Properties\Actions\UpdatePropertyFeatures;
use App\Modules\Properties\Actions\UpdatePropertyLocation;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Enums\UnitTemplate;
use App\Modules\Properties\Models\Property;
use Illuminate\Database\Seeder;

/**
 * A draft property for the development owner, ready to add photos and request inspection. Local only.
 */
class DevelopmentPropertySeeder extends Seeder
{
    public function run(
        CreateProperty $createProperty,
        UpdatePropertyLocation $updateLocation,
        ApplyUnitTemplate $applyTemplate,
        UpdatePropertyFeatures $updateFeatures,
    ): void {
        $owner = User::query()->where('email', 'owner@example.test')->first();

        if (! $owner || Property::query()->where('owner_id', $owner->id)->exists()) {
            return;
        }

        $property = $createProperty->handle($owner, $owner, 'Casa estudiantil cerca de Campus Centro', PropertyType::House, ManagementMode::SelfManaged);
        $property->update(['description' => 'Casa de dos plantas a 10 minutos caminando del campus, con cocina equipada y patio.']);

        $updateLocation->handle($property, [
            'street' => 'Calle Universidad',
            'exterior_number' => '245',
            'interior_number' => null,
            'neighborhood' => 'Zona Centro',
            'city' => 'Tijuana',
            'state' => 'Baja California',
            'postal_code' => '22000',
            'latitude' => 32.5292,
            'longitude' => -117.0279,
        ]);

        $applyTemplate->handle($property, UnitTemplate::Mixed, 3, 4);

        $amenityIds = array_values(Amenity::query()
            ->whereIn('name', ['Internet / wifi', 'Agua caliente', 'Cocina equipada', 'Lavadora'])
            ->get()
            ->map(fn (Amenity $amenity): int => $amenity->id)
            ->all());

        $updateFeatures->handle(
            $property,
            $amenityIds,
            HouseRule::query()->whereIn('name', ['Fumar', 'Mascotas'])->pluck('id')
                ->mapWithKeys(fn (int $id) => [$id => ['is_allowed' => false, 'note' => null]])->all(),
            GenderPolicy::Mixed,
            [],
        );
    }
}
