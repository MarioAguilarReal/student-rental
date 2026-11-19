<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Campus;
use App\Modules\Catalog\Models\Institution;

/**
 * HU-03.01: a campus always has coordinates, used later to search by distance (HU-07.01).
 */
class SaveCampus
{
    public function handle(Institution $institution, ?Campus $campus, string $name, ?string $address, float $latitude, float $longitude): Campus
    {
        $campus ??= new Campus;
        $campus->institution()->associate($institution);
        $campus->fill([
            'name' => $name,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ])->save();

        return $campus;
    }
}
