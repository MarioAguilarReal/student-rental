<?php

namespace App\Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Http\Toast;
use App\Modules\Verification\Actions\SubmitClientVerification;
use App\Modules\Verification\Actions\SubmitOwnerVerification;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Http\Requests\SubmitClientVerificationRequest;
use App\Modules\Verification\Http\Requests\SubmitOwnerVerificationRequest;
use App\Modules\Verification\Models\VerificationDocument;
use App\Modules\Verification\Models\VerificationProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['verificationProfiles.institution', 'verificationProfiles.documents', 'roles']);

        return Inertia::render('verification/show', [
            'profiles' => $user->verificationProfiles
                ->sortBy(fn (VerificationProfile $profile): int => $profile->kind === VerificationKind::Client ? 0 : 1)
                ->values()
                ->map(fn (VerificationProfile $profile): array => [
                    'id' => $profile->id,
                    'kind' => $profile->kind->value,
                    'kind_label' => $profile->kind->label(),
                    'status' => $profile->status->value,
                    'status_label' => $profile->status->label(),
                    'accepts_submissions' => $profile->status->acceptsSubmissions(),
                    'community_type' => $profile->community_type?->value,
                    'institution_id' => $profile->institution_id,
                    'institution' => $profile->institution?->displayName(),
                    'stay_ends_on' => $profile->stay_ends_on?->toDateString(),
                    'submitted_at' => $profile->submitted_at?->toDateString(),
                    'rejection_reason' => $profile->rejection_reason,
                    'verified_until' => $profile->verified_until?->toDateString(),
                    'documents' => $profile->documents->map(fn (VerificationDocument $document): array => [
                        'id' => $document->id,
                        'type' => $document->type->label(),
                        'status' => $document->status->value,
                        'status_label' => $document->status->label(),
                        'name' => $document->original_name,
                        'uploaded_at' => $document->created_at?->toDateString(),
                    ]),
                ]),
            'institutions' => Institution::query()->active()->orderBy('name')->get()
                ->map(fn (Institution $institution): array => ['value' => $institution->id, 'label' => $institution->displayName()]),
            'communityTypes' => CommunityType::options(),
            'missingProfiles' => array_values(array_filter(
                array_map(fn (Role $role): array => ['value' => $role->value, 'label' => $role->label()], Role::selfAssignable()),
                fn (array $option): bool => ! $user->hasRole($option['value']),
            )),
        ]);
    }

    public function storeClient(SubmitClientVerificationRequest $request, SubmitClientVerification $submitClientVerification): RedirectResponse
    {
        $submitClientVerification->handle(
            $this->profile($request, VerificationKind::Client),
            CommunityType::from($request->string('community_type')->toString()),
            Institution::query()->findOrFail($request->integer('institution_id')),
            $request->file('credential'),
            $request->file('acceptance_letter'),
            $request->filled('stay_ends_on') ? CarbonImmutable::parse($request->string('stay_ends_on')->toString()) : null,
        );

        Toast::success(__('Documents sent. We will notify you once they are reviewed.'));

        return back();
    }

    public function storeOwner(SubmitOwnerVerificationRequest $request, SubmitOwnerVerification $submitOwnerVerification): RedirectResponse
    {
        $officialId = $request->file('official_id');
        abort_unless($officialId instanceof UploadedFile, 422);

        $submitOwnerVerification->handle($this->profile($request, VerificationKind::Owner), $officialId);

        Toast::success(__('Documents sent. We will notify you once they are reviewed.'));

        return back();
    }

    private function profile(Request $request, VerificationKind $kind): VerificationProfile
    {
        /** @var User $user */
        $user = $request->user();

        return $user->verificationProfiles()->where('kind', $kind)->firstOrFail();
    }
}
