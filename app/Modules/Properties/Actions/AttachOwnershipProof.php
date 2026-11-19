<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Models\Property;
use Illuminate\Http\UploadedFile;

/**
 * D-49 · RN-06: proof of ownership (or the owner's authorization to rent), one per property.
 * Stored privately (RN-07); a new file replaces the previous one.
 */
class AttachOwnershipProof
{
    public function handle(Property $property, UploadedFile $file): void
    {
        EnsurePropertyIsEditable::check($property);

        $property->addMedia($file)->toMediaCollection(Property::OWNERSHIP_PROOF);
    }
}
