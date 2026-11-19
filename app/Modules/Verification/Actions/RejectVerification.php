<?php

namespace App\Modules\Verification\Actions;

use App\Models\User;
use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationRejected;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Support\Facades\DB;

/**
 * HU-02.04: a rejection always carries a reason the applicant can act on.
 */
class RejectVerification
{
    public function handle(User $reviewer, VerificationProfile $profile, string $reason): VerificationProfile
    {
        EnsureReviewerIsNotTheApplicant::check($reviewer, $profile);

        $profile->status->assertCanTransitionTo(VerificationStatus::Rejected);

        DB::transaction(function () use ($reviewer, $profile, $reason) {
            $profile->documents()
                ->where('status', DocumentStatus::Pending)
                ->update(['status' => DocumentStatus::Rejected]);

            $profile->forceFill([
                'status' => VerificationStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();
        });

        VerificationRejected::dispatch($profile, $reviewer);

        return $profile;
    }
}
