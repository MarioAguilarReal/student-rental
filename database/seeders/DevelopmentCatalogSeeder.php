<?php

namespace Database\Seeders;

use App\Modules\Catalog\Actions\SaveCampus;
use App\Modules\Catalog\Actions\SaveInstitution;
use App\Modules\Catalog\Models\Institution;
use Illuminate\Database\Seeder;

/**
 * Demo institution whose domain matches the development users (@example.test). Local/testing only.
 */
class DevelopmentCatalogSeeder extends Seeder
{
    public function run(SaveInstitution $saveInstitution, SaveCampus $saveCampus): void
    {
        if (Institution::query()->where('name', 'Universidad Demo')->exists()) {
            return;
        }

        $institution = $saveInstitution->handle(null, 'Universidad Demo', 'UDEMO', ['example.test'], true);
        $saveCampus->handle($institution, null, 'Campus Centro', 'Av. Universidad 100', 32.5317, -117.0237);
        $saveCampus->handle($institution, null, 'Campus Valle', 'Blvd. del Valle 200', 32.4520, -116.9150);

        $saveInstitution->handle(null, 'Instituto Tecnológico Demo', 'ITD', ['itdemo.test'], true);
    }
}
