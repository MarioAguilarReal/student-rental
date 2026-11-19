<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * HU-04.03: alternative text, which space the photo shows, cover (first position) and order.
 */
class UpdatePropertyPhoto
{
    public function handle(Property $property, Media $photo, ?string $alt, ?int $unitId, bool $makeCover = false): void
    {
        EnsurePropertyIsEditable::check($property);
        $this->ensureBelongs($property, $photo);

        if ($unitId !== null && ! $property->units()->whereKey($unitId)->exists()) {
            throw new BusinessRuleViolation('HU-04.03', __('Invalid space.'), 'unit_id');
        }

        $photo->setCustomProperty('alt', $alt);
        $photo->setCustomProperty('unit_id', $unitId);
        $photo->save();

        if ($makeCover) {
            $ids = $property->getMedia(Property::PHOTOS)->pluck('id')->reject(fn (int $id) => $id === $photo->id)->values()->all();
            Media::setNewOrder([$photo->id, ...$ids]);
        }
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(Property $property, array $orderedIds): void
    {
        EnsurePropertyIsEditable::check($property);
        $current = $property->getMedia(Property::PHOTOS)->pluck('id')->sort()->values()->all();
        $sorted = $orderedIds;
        sort($sorted);

        if ($current !== $sorted) {
            throw new BusinessRuleViolation('HU-04.03', __('The photo order does not match the property photos.'), 'photos');
        }

        Media::setNewOrder($orderedIds);
    }

    public function delete(Property $property, Media $photo): void
    {
        EnsurePropertyIsEditable::check($property);
        $this->ensureBelongs($property, $photo);
        $photo->delete();
    }

    private function ensureBelongs(Property $property, Media $photo): void
    {
        if ($photo->model_type !== $property->getMorphClass() || (int) $photo->model_id !== $property->id || $photo->collection_name !== Property::PHOTOS) {
            throw new BusinessRuleViolation('HU-04.03', __('Invalid photo.'), 'photos');
        }
    }
}
