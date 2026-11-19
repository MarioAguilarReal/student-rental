<?php

namespace Database\Seeders;

use App\Modules\Catalog\Enums\AmenityCategory as C;
use App\Modules\Catalog\Enums\AmenityScope as S;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use Illuminate\Database\Seeder;

/**
 * Initial amenities and house rules (HU-03.02). Idempotent; runs in every environment.
 * Super Admins maintain the catalog from the UI afterwards.
 */
class CatalogDefaultsSeeder extends Seeder
{
    private const AMENITIES = [
        ['Internet / wifi', C::Basics, S::Property],
        ['Agua caliente', C::Basics, S::Property],
        ['Luz y agua incluidas', C::Basics, S::Property],
        ['Amueblado', C::Basics, S::Both],
        ['Cocina equipada', C::Kitchen, S::Property],
        ['Refrigerador', C::Kitchen, S::Both],
        ['Microondas', C::Kitchen, S::Property],
        ['Baño privado', C::Bathroom, S::Unit],
        ['Lavadora', C::Laundry, S::Property],
        ['Área de lavado', C::Laundry, S::Property],
        ['Escritorio', C::Study, S::Unit],
        ['Clóset', C::Comfort, S::Unit],
        ['Aire acondicionado', C::Comfort, S::Both],
        ['Calefacción', C::Comfort, S::Both],
        ['Cerradura en la habitación', C::Safety, S::Unit],
        ['Detector de humo', C::Safety, S::Property],
        ['Extintor', C::Safety, S::Property],
        ['Vigilancia / acceso controlado', C::Safety, S::Property],
        ['Estacionamiento', C::Outdoor, S::Property],
        ['Patio o terraza', C::Outdoor, S::Property],
    ];

    private const HOUSE_RULES = [
        ['Mascotas', 'Se permiten animales de compañía.'],
        ['Fumar', 'Se permite fumar dentro de la propiedad.'],
        ['Fiestas o eventos', 'Se permiten reuniones con invitados.'],
        ['Visitas nocturnas', 'Se permite que las visitas se queden a dormir.'],
        ['Horario de silencio', 'Hay un horario de silencio que todos deben respetar.'],
    ];

    public function run(): void
    {
        foreach (self::AMENITIES as [$name, $category, $scope]) {
            Amenity::query()->firstOrCreate(['name' => $name], ['category' => $category, 'scope' => $scope]);
        }

        foreach (self::HOUSE_RULES as [$name, $description]) {
            HouseRule::query()->firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
