<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * HU-04.01 / HU-04.05: the address and map pin. Changing them is critical (RN-11).
 */
class UpdatePropertyLocation
{
    public function __construct(private MarkForReinspection $markForReinspection) {}

    /**
     * @param  array{street: string, exterior_number: string, interior_number: ?string, neighborhood: string, city: string, state: string, postal_code: string, latitude: float, longitude: float}  $location
     */
    public function handle(Property $property, array $location): Property
    {
        EnsurePropertyIsEditable::check($property);

        DB::transaction(function () use ($property, $location) {
            $property->fill($location);

            if ($property->isDirty()) {
                $property->save();
                $this->markForReinspection->handle($property);
            }
        });

        return $property;
    }
}
