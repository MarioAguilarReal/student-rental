<?php

namespace Database\Seeders;

use App\Modules\Catalog\Actions\SaveCampus;
use App\Modules\Catalog\Actions\SaveInstitution;
use App\Modules\Catalog\Models\Institution;
use Illuminate\Database\Seeder;

/**
 * Demo institution whose domain matches the development users (@example.test), with campuses
 * in Tepic, Nayarit. Local/testing only.
 */
class DevelopmentCatalogSeeder extends Seeder
{
    public function run(SaveInstitution $saveInstitution, SaveCampus $saveCampus): void
    {
        if (Institution::query()->where('name', 'Universidad Demo')->exists()) {
            return;
        }

        $institution = $saveInstitution->handle(null, 'Universidad Demo', 'UDEMO', ['example.test'], true);
        $saveCampus->handle($institution, null, 'Campus Centro', 'Av. México 100, Centro, Tepic', 21.5095, -104.8946);
        $saveCampus->handle($institution, null, 'Campus Ciudad del Valle', 'Blvd. Tepic-Xalisco 200, Tepic', 21.4930, -104.8890);

        $saveInstitution->handle(null, 'Instituto Tecnológico Demo', 'ITD', ['itdemo.test'], true);
    }
}
