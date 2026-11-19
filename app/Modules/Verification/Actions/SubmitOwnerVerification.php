<?php

namespace App\Modules\Verification\Actions;

use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationSubmitted;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * HU-02.03 · RN-06 (identity part): an owner proves who they are with an official ID.
 * Proof of ownership is attached per property before requesting its inspection (Iteración 2).
 */
class SubmitOwnerVerification
{
    public function __construct(private StoreVerificationDocument $storeDocument) {}

    public function handle(VerificationProfile $profile, UploadedFile $officialId): VerificationProfile
    {
        if ($profile->kind !== VerificationKind::Owner) {
            throw new BusinessRuleViolation('RN-06', __('This is not an owner profile.'));
        }

        $profile->status->assertCanTransitionTo(VerificationStatus::InReview);

        DB::transaction(function () use ($profile, $officialId) {
            $this->storeDocument->handle($profile, DocumentType::OfficialId, $officialId);

            $profile->forceFill([
                'status' => VerificationStatus::InReview,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ])->save();
        });

        VerificationSubmitted::dispatch($profile);

        return $profile;
    }
}
