<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Identity\Actions\AddAccountProfile;
use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use Illuminate\Database\Seeder;

/**
 * Ten published, inspected demo listings in Tepic, Nayarit (around the UAN and downtown) with
 * openly licensed photos (database/seeders/photos, credited in CREDITS.md; a coloured
 * placeholder if one is missing), so the public catalog has something to show. Local only;
 * skipped if the demo host already has properties. Addresses and postal codes are illustrative.
 */
class DevelopmentListingsSeeder extends Seeder
{
    /**
     * @var list<array{title: string, type: PropertyType, street: string, number: string, neighborhood: string, postal_code: string, lat: float, lng: float, gender: GenderPolicy, communities: list<string>, mode: ManagementMode, units: list<array{UnitType, string, int, int}>, color: string}>
     */
    private const LISTINGS = [
        [
            'title' => 'Departamento amueblado frente a Ciudad de la Cultura', 'type' => PropertyType::Apartment,
            'street' => 'Blvd. Tepic-Xalisco', 'number' => '412', 'neighborhood' => 'Ciudad del Valle', 'postal_code' => '63157',
            'lat' => 21.4941, 'lng' => -104.8921, 'gender' => GenderPolicy::Mixed, 'communities' => [], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::EntirePlace, 'Departamento completo', 2, 650_000]], 'color' => '#d9e8ec',
        ],
        [
            'title' => 'Casa de estudiantes con patio cerca de la UAN', 'type' => PropertyType::House,
            'street' => 'Calle Lago de Chapala', 'number' => '58', 'neighborhood' => 'Lagos del Country', 'postal_code' => '63173',
            'lat' => 21.4978, 'lng' => -104.8862, 'gender' => GenderPolicy::Mixed, 'communities' => [], 'mode' => ManagementMode::Managed,
            'units' => [[UnitType::PrivateRoom, 'Habitación 1', 1, 280_000], [UnitType::PrivateRoom, 'Habitación 2', 1, 300_000], [UnitType::PrivateRoom, 'Habitación 3', 1, 320_000]], 'color' => '#ece4d9',
        ],
        [
            'title' => 'Residencia solo mujeres a dos cuadras del campus', 'type' => PropertyType::House,
            'street' => 'Calle Fresno', 'number' => '17', 'neighborhood' => 'Los Fresnos', 'postal_code' => '63190',
            'lat' => 21.4897, 'lng' => -104.8968, 'gender' => GenderPolicy::WomenOnly, 'communities' => [], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::PrivateRoom, 'Habitación con baño', 1, 350_000], [UnitType::PrivateRoom, 'Habitación doble', 2, 420_000]], 'color' => '#ecd9e4',
        ],
        [
            'title' => 'Cama en cuarto compartido, ideal para intercambio', 'type' => PropertyType::RoomInHouse,
            'street' => 'Av. Insurgentes', 'number' => '1030', 'neighborhood' => 'Versalles', 'postal_code' => '63138',
            'lat' => 21.5012, 'lng' => -104.8985, 'gender' => GenderPolicy::Mixed, 'communities' => [], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::Bed, 'Cama A', 1, 180_000], [UnitType::Bed, 'Cama B', 1, 180_000]], 'color' => '#dfecd9',
        ],
        [
            'title' => 'Loft en el centro histórico de Tepic', 'type' => PropertyType::Apartment,
            'street' => 'Calle Amado Nervo', 'number' => '85', 'neighborhood' => 'Centro', 'postal_code' => '63000',
            'lat' => 21.5101, 'lng' => -104.8952, 'gender' => GenderPolicy::Mixed, 'communities' => [], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::EntirePlace, 'Loft completo', 1, 550_000]], 'color' => '#e6e0ec',
        ],
        [
            'title' => 'Casa para docentes con estudio', 'type' => PropertyType::House,
            'street' => 'Calle Durango', 'number' => '240', 'neighborhood' => 'San Juan', 'postal_code' => '63130',
            'lat' => 21.5048, 'lng' => -104.8870, 'gender' => GenderPolicy::Mixed, 'communities' => [CommunityType::Faculty->value], 'mode' => ManagementMode::Managed,
            'units' => [[UnitType::EntirePlace, 'Casa completa', 4, 950_000]], 'color' => '#ecead9',
        ],
        [
            'title' => 'Habitaciones para hombres junto a la avenida México', 'type' => PropertyType::House,
            'street' => 'Av. México', 'number' => '612', 'neighborhood' => 'Burócrata Federal', 'postal_code' => '63156',
            'lat' => 21.4992, 'lng' => -104.8935, 'gender' => GenderPolicy::MenOnly, 'communities' => [], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::PrivateRoom, 'Habitación azul', 1, 260_000], [UnitType::PrivateRoom, 'Habitación verde', 1, 260_000]], 'color' => '#d9e1ec',
        ],
        [
            'title' => 'Departamento con terraza en Jardines de la Cruz', 'type' => PropertyType::Apartment,
            'street' => 'Calle Jacarandas', 'number' => '33', 'neighborhood' => 'Jardines de la Cruz', 'postal_code' => '63168',
            'lat' => 21.4868, 'lng' => -104.8890, 'gender' => GenderPolicy::Mixed, 'communities' => [], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::EntirePlace, 'Departamento completo', 3, 780_000]], 'color' => '#d9ece6',
        ],
        [
            'title' => 'Casa compartida para estudiantes de posgrado', 'type' => PropertyType::House,
            'street' => 'Calle Hidalgo', 'number' => '318', 'neighborhood' => 'Centro', 'postal_code' => '63000',
            'lat' => 21.5078, 'lng' => -104.8913, 'gender' => GenderPolicy::Mixed, 'communities' => [CommunityType::Student->value, CommunityType::ExchangeStudent->value], 'mode' => ManagementMode::SelfManaged,
            'units' => [[UnitType::PrivateRoom, 'Habitación planta alta', 1, 300_000], [UnitType::PrivateRoom, 'Habitación planta baja', 1, 290_000], [UnitType::PrivateRoom, 'Habitación con balcón', 1, 330_000]], 'color' => '#ece0d9',
        ],
        [
            'title' => 'Estudio independiente en Ciudad del Valle', 'type' => PropertyType::Apartment,
            'street' => 'Calle Paseo de los Sauces', 'number' => '9', 'neighborhood' => 'Ciudad del Valle', 'postal_code' => '63157',
            'lat' => 21.4925, 'lng' => -104.8855, 'gender' => GenderPolicy::Mixed, 'communities' => [], 'mode' => ManagementMode::Managed,
            'units' => [[UnitType::EntirePlace, 'Estudio completo', 1, 480_000]], 'color' => '#e1ecd9',
        ],
    ];

    public function run(AddAccountProfile $addAccountProfile): void
    {
        $host = User::query()->where('email', 'anfitriona@example.test')->first();

        if ($host && Property::query()->where('owner_id', $host->id)->exists()) {
            return;
        }

        if (! $host) {
            $host = User::factory()->create(['first_name' => 'Laura', 'last_name' => 'Anfitriona', 'email' => 'anfitriona@example.test']);
            $addAccountProfile->handle($host, Role::Owner);
            $host->verificationProfiles()->where('kind', VerificationKind::Owner)->update(['status' => VerificationStatus::Verified, 'reviewed_at' => now()]);
        }

        $amenities = Amenity::query()->whereIn('name', ['Internet / wifi', 'Agua caliente', 'Cocina equipada', 'Lavadora', 'Escritorio'])->get();

        foreach (self::LISTINGS as $index => $listing) {
            $property = Property::create([
                'owner_id' => $host->id, 'created_by' => $host->id, 'management_mode' => $listing['mode'], 'title' => $listing['title'],
                'description' => 'Espacio de demostración para probar el catálogo en Tepic. Con transporte público cerca y comercios a la mano.',
                'property_type' => $listing['type'], 'street' => $listing['street'], 'exterior_number' => $listing['number'],
                'neighborhood' => $listing['neighborhood'], 'city' => 'Tepic', 'state' => 'Nayarit', 'postal_code' => $listing['postal_code'],
                'latitude' => $listing['lat'], 'longitude' => $listing['lng'],
                'status' => PropertyStatus::Published, 'gender_policy' => $listing['gender'], 'allowed_communities' => $listing['communities'],
                'inspection_valid_until' => now()->addYear(), 'published_at' => now()->subDays($index),
            ]);
            $property->amenities()->sync($amenities->modelKeys());

            $units = $listing['units'];
            $parent = null;
            if (in_array($units[0][0], [UnitType::PrivateRoom, UnitType::Bed], true)) {
                $parent = Unit::create(['property_id' => $property->id, 'type' => UnitType::EntirePlace, 'name' => 'Casa completa', 'capacity' => count($units), 'is_rentable' => false]);
                if ($units[0][0] === UnitType::Bed) {
                    $parent = Unit::create(['property_id' => $property->id, 'parent_id' => $parent->id, 'type' => UnitType::SharedRoom, 'name' => 'Cuarto compartido', 'capacity' => count($units), 'is_rentable' => false]);
                }
            }

            foreach ($units as $position => [$unitType, $name, $capacity, $monthly]) {
                $unit = Unit::create(['property_id' => $property->id, 'parent_id' => $parent?->id, 'type' => $unitType, 'name' => $name, 'capacity' => $capacity, 'is_rentable' => true, 'position' => $position]);
                RatePlan::create(['unit_id' => $unit->id, 'period' => RatePeriod::Monthly, 'price_minor' => $monthly, 'min_periods' => 1, 'max_periods' => 12]);
                RatePlan::create(['unit_id' => $unit->id, 'period' => RatePeriod::Weekly, 'price_minor' => (int) (round($monthly / 4 * 1.15 / 1000) * 1000), 'min_periods' => 1, 'max_periods' => 12]);
                UnitDeposit::create(['unit_id' => $unit->id, 'amount_minor' => $monthly]);
            }

            $this->attachPhotos($property, $index, $units[0][0] === UnitType::Bed, $listing['color']);
        }
    }

    /**
     * Façade, living room and bedroom (dormitory for shared beds) from the demo photo bank.
     */
    public function attachPhotos(Property $property, int $index, bool $sharedBeds, string $fallbackColor): void
    {
        $photos = [
            'Fachada' => 'facade-'.($index % 6 + 1),
            'Sala' => 'living-'.(($index + 2) % 6 + 1),
            'Habitación' => $sharedBeds ? 'dorm-'.($index % 2 + 1) : 'bedroom-'.($index % 4 + 1),
        ];

        foreach ($photos as $label => $file) {
            $path = database_path("seeders/photos/{$file}.jpg");
            $adder = is_file($path)
                ? $property->addMedia($path)->preservingOriginal()
                : $property->addMediaFromString($this->placeholderJpeg($fallbackColor, $label));

            $adder->usingFileName(strtolower($label).'.jpg')
                ->withCustomProperties(['alt' => "{$label} (foto de demostración)", 'unit_id' => null])
                ->toMediaCollection(Property::PHOTOS);
        }
    }

    private function placeholderJpeg(string $hex, string $label): string
    {
        $image = imagecreatetruecolor(1200, 900);
        $channel = fn (int $offset): int => max(0, min(255, (int) hexdec(substr($hex, $offset, 2))));
        [$r, $g, $b] = [$channel(1), $channel(3), $channel(5)];
        $darker = fn (int $channel): int => max(0, min(255, $channel - 25));
        imagefill($image, 0, 0, (int) imagecolorallocate($image, $r, $g, $b));
        $ink = (int) imagecolorallocate($image, 60, 70, 75);
        imagefilledrectangle($image, 380, 300, 820, 600, (int) imagecolorallocate($image, $darker($r), $darker($g), $darker($b)));
        imagestring($image, 5, 540, 600 + 20, $label, $ink);
        ob_start();
        imagejpeg($image, null, 80);

        return (string) ob_get_clean();
    }
}
