<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, CatalogDefaultsSeeder::class]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([DevelopmentCatalogSeeder::class, DevelopmentUsersSeeder::class]);
        }

        if (app()->environment('local')) {
            $this->call([DevelopmentVerificationSeeder::class, DevelopmentPropertySeeder::class]);
        }
    }
}
