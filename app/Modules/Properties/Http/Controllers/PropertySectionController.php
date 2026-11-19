<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Actions\AttachOwnershipProof;
use App\Modules\Properties\Actions\UpdatePropertyFeatures;
use App\Modules\Properties\Actions\UpdatePropertyLocation;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Http\Requests\OwnershipProofRequest;
use App\Modules\Properties\Http\Requests\UpdatePropertyFeaturesRequest;
use App\Modules\Properties\Http\Requests\UpdatePropertyLocationRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Sections of the property editor: location, features and proof of ownership.
 */
class PropertySectionController extends Controller
{
    public function location(UpdatePropertyLocationRequest $request, Property $property, UpdatePropertyLocation $updateLocation): RedirectResponse
    {
        Gate::authorize('update', $property);

        $wasInspected = $property->status->hasPassedInspection();
        $updateLocation->handle($property, [
            'street' => $request->string('street')->toString(),
            'exterior_number' => $request->string('exterior_number')->toString(),
            'interior_number' => $request->input('interior_number'),
            'neighborhood' => $request->string('neighborhood')->toString(),
            'city' => $request->string('city')->toString(),
            'state' => $request->string('state')->toString(),
            'postal_code' => $request->string('postal_code')->toString(),
            'latitude' => $request->float('latitude'),
            'longitude' => $request->float('longitude'),
        ]);

        Toast::success($wasInspected && ! $property->status->hasPassedInspection()
            ? __('Location saved. The property will be inspected again.')
            : __('Location saved.'));

        return back();
    }

    public function features(UpdatePropertyFeaturesRequest $request, Property $property, UpdatePropertyFeatures $updateFeatures): RedirectResponse
    {
        Gate::authorize('update', $property);

        $updateFeatures->handle(
            $property,
            array_values(array_map('intval', $request->array('amenity_ids'))),
            $request->houseRules(),
            GenderPolicy::from($request->string('gender_policy')->toString()),
            array_values($request->array('allowed_communities')),
        );

        Toast::success(__('Changes saved.'));

        return back();
    }

    public function ownershipProof(OwnershipProofRequest $request, Property $property, AttachOwnershipProof $attachOwnershipProof): RedirectResponse
    {
        Gate::authorize('update', $property);

        $document = $request->file('document');
        abort_unless($document instanceof UploadedFile, 422);
        $attachOwnershipProof->handle($property, $document);

        Toast::success(__('Proof of ownership attached.'));

        return back();
    }
}
