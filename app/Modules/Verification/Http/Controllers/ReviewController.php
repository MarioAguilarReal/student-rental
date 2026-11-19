<?php

namespace App\Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Shared\Http\Toast;
use App\Modules\Verification\Actions\ApproveVerification;
use App\Modules\Verification\Actions\RejectVerification;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Http\Requests\RejectVerificationRequest;
use App\Modules\Verification\Models\VerificationDocument;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-02.04: review inbox for administrators.
 */
class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $kind = VerificationKind::tryFrom($request->string('kind')->toString());

        $profiles = VerificationProfile::query()
            ->with(['user', 'institution'])
            ->withCount('documents')
            ->where('status', VerificationStatus::InReview)
            ->when($kind, fn ($query) => $query->where('kind', $kind))
            ->oldest('submitted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VerificationProfile $profile): array => [
                'id' => $profile->id,
                'user' => ['id' => $profile->user->id, 'name' => $profile->user->name, 'email' => $profile->user->email],
                'kind' => $profile->kind->label(),
                'community_type' => $profile->community_type?->label(),
                'institution' => $profile->institution?->displayName(),
                'submitted_at' => $profile->submitted_at?->toDateTimeString(),
                'waiting' => $profile->submitted_at?->diffForHumans(),
                'documents_count' => $profile->documents_count,
            ]);

        $counts = VerificationProfile::query()
            ->where('status', VerificationStatus::InReview)
            ->toBase()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');

        return Inertia::render('verification/reviews/index', [
            'profiles' => $profiles,
            'kindCounts' => [
                'all' => (int) $counts->sum(),
                ...collect(VerificationKind::cases())->mapWithKeys(fn (VerificationKind $case) => [$case->value => (int) ($counts[$case->value] ?? 0)])->all(),
            ],
            'filters' => ['kind' => $kind?->value],
            'kindOptions' => VerificationKind::options(),
        ]);
    }

    public function show(Request $request, VerificationProfile $profile): Response
    {
        $profile->load(['user', 'institution', 'documents', 'reviewer']);

        return Inertia::render('verification/reviews/show', [
            'profile' => [
                'id' => $profile->id,
                'user' => [
                    'id' => $profile->user->id,
                    'name' => $profile->user->name,
                    'email' => $profile->user->email,
                    'birth_date' => $profile->user->birth_date?->toDateString(),
                ],
                'kind' => $profile->kind->label(),
                'status' => $profile->status->value,
                'status_label' => $profile->status->label(),
                'community_type' => $profile->community_type?->label(),
                'institution' => $profile->institution?->displayName(),
                'stay_ends_on' => $profile->stay_ends_on?->toDateString(),
                'submitted_at' => $profile->submitted_at?->toDateTimeString(),
                'reviewed_at' => $profile->reviewed_at?->toDateTimeString(),
                'reviewer' => $profile->reviewer?->name,
                'rejection_reason' => $profile->rejection_reason,
                'verified_until' => $profile->verified_until?->toDateString(),
                'documents' => $profile->documents->map(fn (VerificationDocument $document): array => [
                    'id' => $document->id,
                    'type' => $document->type->label(),
                    'status' => $document->status->value,
                    'status_label' => $document->status->label(),
                    'name' => $document->original_name,
                    'mime_type' => $document->mime_type,
                    'url' => $document->temporaryUrl(),
                    'uploaded_at' => $document->created_at?->toDateTimeString(),
                ]),
            ],
            'canDecide' => $profile->status === VerificationStatus::InReview && $profile->user_id !== $request->user()?->id,
        ]);
    }

    public function approve(Request $request, VerificationProfile $profile, ApproveVerification $approveVerification): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        $approveVerification->handle($reviewer, $profile);

        Toast::success(__('Verification approved.'));

        return to_route('verifications.reviews.index');
    }

    public function reject(RejectVerificationRequest $request, VerificationProfile $profile, RejectVerification $rejectVerification): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = $request->user();
        $rejectVerification->handle($reviewer, $profile, $request->string('reason')->toString());

        Toast::success(__('Verification rejected. The applicant has been notified.'));

        return to_route('verifications.reviews.index');
    }
}
