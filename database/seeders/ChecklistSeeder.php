<?php

namespace Database\Seeders;

use App\Modules\Inspections\Enums\ChecklistCategory as C;
use App\Modules\Inspections\Enums\ChecklistStatus;
use App\Modules\Inspections\Enums\ItemScope as S;
use App\Modules\Inspections\Models\ChecklistTemplate;
use Illuminate\Database\Seeder;

/**
 * Version 1 of the inspection checklist (HU-03.03). Runs in every environment and only when
 * there is no checklist yet; later versions are edited by a Super Admin from the UI.
 * Item texts are content (Spanish), not UI keys.
 */
class ChecklistSeeder extends Seeder
{
    /**
     * @var list<array{0: C, 1: string, 2: S, 3: bool, 4: bool}> category, text, scope, critical, photo
     */
    private const ITEMS = [
        [C::Electrical, 'Instalación eléctrica sin cables expuestos y con centro de carga accesible', S::Property, true, true],
        [C::Gas, 'Instalación de gas sin fugas, con tanque o medidor en un área ventilada', S::Property, true, true],
        [C::FireSafety, 'Extintor vigente y a la vista en un área común', S::Property, true, true],
        [C::FireSafety, 'Detector de humo instalado y funcionando', S::Property, true, false],
        [C::Security, 'Chapa de la puerta principal en buen estado', S::Property, true, false],
        [C::Structure, 'Ruta de salida libre y señalada', S::Property, true, false],
        [C::Structure, 'Sin humedad, grietas ni daños estructurales visibles', S::Property, false, true],
        [C::Hygiene, 'Agua potable y drenaje funcionando', S::Property, true, false],
        [C::Hygiene, 'Cocina y baños comunes limpios y funcionales', S::Property, false, false],
        [C::Listing, 'Las fotos publicadas corresponden al estado real', S::Property, false, false],
        [C::Security, 'El espacio tiene cerradura propia', S::Unit, false, false],
        [C::Electrical, 'Contactos e iluminación del espacio funcionando', S::Unit, false, false],
        [C::Structure, 'Ventilación e iluminación natural adecuadas', S::Unit, false, false],
        [C::Listing, 'La capacidad declarada corresponde al espacio', S::Unit, false, true],
    ];

    public function run(): void
    {
        if (ChecklistTemplate::query()->exists()) {
            return;
        }

        $template = ChecklistTemplate::create([
            'name' => 'Seguridad y fidelidad de la publicación',
            'version' => 1,
            'status' => ChecklistStatus::Published,
            'published_at' => now(),
        ]);

        foreach (self::ITEMS as $position => [$category, $text, $scope, $critical, $photo]) {
            $template->items()->create([
                'category' => $category,
                'text' => $text,
                'scope' => $scope,
                'is_critical' => $critical,
                'requires_photo' => $photo,
                'position' => $position,
            ]);
        }
    }
}
