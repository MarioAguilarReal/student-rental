<?php

namespace App\Modules\Verification\Actions;

use App\Models\User;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationApproved;
use App\Modules\Verification\Models\VerificationProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-02.04 · RN-04: approving sets how long the verification lasts.
 * RN-08: nobody reviews their own verification.
 */
class ApproveVerification
{
    public function handle(User $reviewer, VerificationProfile $profile): VerificationProfile
    {
        EnsureReviewerIsNotTheApplicant::check($reviewer, $profile);

        $profile->status->assertCanTransitionTo(VerificationStatus::Verified);

        DB::transaction(function () use ($reviewer, $profile) {
            $profile->documents()
                ->where('status', DocumentStatus::Pending)
                ->update(['status' => DocumentStatus::Approved]);

            $profile->forceFill([
                'status' => VerificationStatus::Verified,
                'verified_until' => $this->validUntil($profile),
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'expiry_reminder_sent_at' => null,
            ])->save();
        });

        VerificationApproved::dispatch($profile, $reviewer);

        return $profile;
    }

    /**
     * RN-04: faculty 12 months, students 6, exchange students until their stay ends
     * (max. 12 months) when they verified with an acceptance letter. Owners do not expire.
     */
    private function validUntil(VerificationProfile $profile): ?CarbonImmutable
    {
        if ($profile->kind === VerificationKind::Owner) {
            return null;
        }

        $today = CarbonImmutable::today();
        $communityType = $profile->community_type ?? throw new BusinessRuleViolation('RN-03', __('The profile has no community type.'));

        $usedAcceptanceLetter = $profile->documents()
            ->where('status', DocumentStatus::Approved)
            ->where('type', DocumentType::AcceptanceLetter)
            ->exists();

        if ($communityType === CommunityType::ExchangeStudent && $usedAcceptanceLetter && $profile->stay_ends_on) {
            return $profile->stay_ends_on->min($today->addMonthsNoOverflow(12));
        }

        return $today->addMonthsNoOverflow($communityType->validityMonths());
    }
}
