<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Presenters\PropertyPresenter;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Home after sign-in: what needs the user's attention, then their things (docs/spec/09 §Inicio).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, PropertyPresenter $propertyPresenter): Response
    {
        /** @var User $user */
        $user = $request->user();
        $ownsProperties = $user->can(Permission::PropertiesCreate->value);

        $properties = $ownsProperties
            ? Property::query()->where('owner_id', $user->id)->where('status', '!=', PropertyStatus::Archived)
                ->with('owner')->withCount('units')->latest('updated_at')->limit(4)->get()
            : collect();

        return Inertia::render('dashboard', [
            'firstName' => $user->first_name,
            'verifications' => $user->verificationProfiles()->get()->map(fn (VerificationProfile $profile): array => [
                'id' => $profile->id,
                'kind_label' => $profile->kind->label(),
                'status' => $profile->status->value,
                'status_label' => $profile->status->label(),
                'verified_until' => $profile->verified_until?->toDateString(),
                'needs_action' => $profile->status->acceptsSubmissions(),
            ]),
            'properties' => $ownsProperties ? $properties->map(fn (Property $property): array => $propertyPresenter->summary($property)) : null,
            'pendingReviews' => $user->can(Permission::VerificationDocumentsReview->value)
                ? VerificationProfile::query()->where('status', VerificationStatus::InReview)->count()
                : null,
        ]);
    }
}
