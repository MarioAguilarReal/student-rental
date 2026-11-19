<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Http\UploadedFile;

/**
 * HU-04.03: up to 30 photos per property, optionally tied to one of its units.
 * Photos are not a critical change (RN-11).
 */
class AddPropertyPhotos
{
    /**
     * @param  list<UploadedFile>  $files
     */
    public function handle(Property $property, array $files, ?Unit $unit = null): void
    {
        EnsurePropertyIsEditable::check($property);

        if ($unit && $unit->property_id !== $property->id) {
            throw new BusinessRuleViolation('HU-04.03', __('Invalid space.'), 'unit_id');
        }

        $current = $property->getMedia(Property::PHOTOS)->count();

        if ($current + count($files) > Property::MAX_PHOTOS) {
            throw new BusinessRuleViolation('HU-04.03', __('A property can have up to :max photos. You can add :left more.', [
                'max' => Property::MAX_PHOTOS,
                'left' => max(0, Property::MAX_PHOTOS - $current),
            ]), 'photos');
        }

        foreach ($files as $file) {
            $property->addMedia($file)
                ->withCustomProperties(['unit_id' => $unit?->id, 'alt' => null])
                ->toMediaCollection(Property::PHOTOS);
        }
    }
}
