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
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use Illuminate\Database\Seeder;

/**
 * Published, inspected demo listings around Campus Centro with generated placeholder photos,
 * so the public catalog has something to show. Local only; skipped if the demo host exists.
 */
class DevelopmentListingsSeeder extends Seeder
{
    private const LISTINGS = [
        ['Departamento luminoso a 5 minutos del campus', PropertyType::Apartment, 32.5340, -117.0210, GenderPolicy::Mixed, ManagementMode::SelfManaged, [[UnitType::EntirePlace, 'Departamento completo', 3, 1_100_000]], '#d9e8ec'],
        ['Casa de estudiantes con patio', PropertyType::House, 32.5270, -117.0300, GenderPolicy::Mixed, ManagementMode::Managed, [[UnitType::PrivateRoom, 'Habitación 1', 1, 420_000], [UnitType::PrivateRoom, 'Habitación 2', 1, 450_000], [UnitType::PrivateRoom, 'Habitación 3', 1, 480_000]], '#ece4d9'],
        ['Residencia solo mujeres cerca de la biblioteca', PropertyType::House, 32.5380, -117.0180, GenderPolicy::WomenOnly, ManagementMode::SelfManaged, [[UnitType::PrivateRoom, 'Habitación con baño', 1, 520_000], [UnitType::PrivateRoom, 'Habitación doble', 2, 610_000]], '#ecd9e4'],
        ['Cama en cuarto compartido, ideal para intercambio', PropertyType::RoomInHouse, 32.5220, -117.0150, GenderPolicy::Mixed, ManagementMode::SelfManaged, [[UnitType::Bed, 'Cama A', 1, 250_000], [UnitType::Bed, 'Cama B', 1, 250_000]], '#dfecd9'],
    ];

    public function run(AddAccountProfile $addAccountProfile): void
    {
        if (User::query()->where('email', 'anfitriona@example.test')->exists()) {
            return;
        }

        $host = User::factory()->create(['first_name' => 'Laura', 'last_name' => 'Anfitriona', 'email' => 'anfitriona@example.test']);
        $addAccountProfile->handle($host, Role::Owner);
        $host->verificationProfiles()->where('kind', VerificationKind::Owner)->update(['status' => VerificationStatus::Verified, 'reviewed_at' => now()]);
        $amenities = Amenity::query()->whereIn('name', ['Internet / wifi', 'Agua caliente', 'Cocina equipada', 'Lavadora', 'Escritorio'])->get();

        foreach (self::LISTINGS as $index => [$title, $type, $lat, $lng, $gender, $mode, $units, $color]) {
            $property = Property::create([
                'owner_id' => $host->id, 'created_by' => $host->id, 'management_mode' => $mode, 'title' => $title,
                'description' => 'Espacio de demostración para probar el catálogo. A pocos minutos del campus, con transporte cerca.',
                'property_type' => $type, 'street' => 'Calle Demo', 'exterior_number' => (string) (100 + $index), 'neighborhood' => 'Zona Centro',
                'city' => 'Tijuana', 'state' => 'Baja California', 'postal_code' => '22000', 'latitude' => $lat, 'longitude' => $lng,
                'status' => PropertyStatus::Published, 'gender_policy' => $gender, 'inspection_valid_until' => now()->addYear(), 'published_at' => now()->subDays($index),
            ]);
            $property->amenities()->sync($amenities->modelKeys());

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

            foreach (['Fachada', 'Sala', 'Habitación'] as $label) {
                $property->addMediaFromString($this->placeholderJpeg($color, $label))
                    ->usingFileName(strtolower($label).'.jpg')
                    ->withCustomProperties(['alt' => "{$label} (foto de demostración)", 'unit_id' => null])
                    ->toMediaCollection(Property::PHOTOS);
            }
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
