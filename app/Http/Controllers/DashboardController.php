<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page after sign-in: a summary per module the user takes part in.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('dashboard', [
            'verifications' => $user->verificationProfiles()->get()->map(fn (VerificationProfile $profile): array => [
                'id' => $profile->id,
                'kind_label' => $profile->kind->label(),
                'status' => $profile->status->value,
                'status_label' => $profile->status->label(),
                'verified_until' => $profile->verified_until?->toDateString(),
                'needs_action' => $profile->status->acceptsSubmissions(),
            ]),
            'pendingReviews' => $user->can(Permission::VerificationDocumentsReview->value)
                ? VerificationProfile::query()->where('status', VerificationStatus::InReview)->count()
                : null,
        ]);
    }
}
