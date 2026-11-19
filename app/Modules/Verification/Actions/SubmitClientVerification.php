<?php

namespace App\Modules\Verification\Actions;

use App\Modules\Catalog\Models\Institution;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Events\VerificationSubmitted;
use App\Modules\Verification\Models\VerificationProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * HU-02.02 · RN-02, RN-03: a client sends their community type, institution and documents.
 * Faculty and students need a credential; exchange students a credential or an
 * acceptance letter, which must state when their stay ends.
 */
class SubmitClientVerification
{
    public function __construct(private StoreVerificationDocument $storeDocument) {}

    public function handle(
        VerificationProfile $profile,
        CommunityType $communityType,
        Institution $institution,
        ?UploadedFile $credential,
        ?UploadedFile $acceptanceLetter,
        ?CarbonImmutable $stayEndsOn,
    ): VerificationProfile {
        if ($profile->kind !== VerificationKind::Client) {
            throw new BusinessRuleViolation('RN-03', __('This is not a client profile.'));
        }

        $profile->status->assertCanTransitionTo(VerificationStatus::InReview);

        if (! $institution->is_active) {
            throw new BusinessRuleViolation('RN-02', __('The selected institution is not available.'), 'institution_id');
        }

        $this->ensureRequiredDocuments($communityType, $credential, $acceptanceLetter, $stayEndsOn);

        DB::transaction(function () use ($profile, $communityType, $institution, $credential, $acceptanceLetter, $stayEndsOn) {
            if ($credential) {
                $this->storeDocument->handle($profile, DocumentType::Credential, $credential);
            }

            if ($acceptanceLetter) {
                $this->storeDocument->handle($profile, DocumentType::AcceptanceLetter, $acceptanceLetter);
            }

            $profile->forceFill([
                'status' => VerificationStatus::InReview,
                'community_type' => $communityType,
                'institution_id' => $institution->id,
                'stay_ends_on' => $acceptanceLetter ? $stayEndsOn : null,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ])->save();
        });

        VerificationSubmitted::dispatch($profile);

        return $profile;
    }

    private function ensureRequiredDocuments(
        CommunityType $communityType,
        ?UploadedFile $credential,
        ?UploadedFile $acceptanceLetter,
        ?CarbonImmutable $stayEndsOn,
    ): void {
        if ($communityType !== CommunityType::ExchangeStudent && ! $credential) {
            throw new BusinessRuleViolation('RN-03', __('Upload your current institutional ID card.'), 'credential');
        }

        if ($communityType === CommunityType::ExchangeStudent && ! $credential && ! $acceptanceLetter) {
            throw new BusinessRuleViolation('RN-03', __('Upload your institutional ID card or your acceptance letter.'), 'credential');
        }

        if ($acceptanceLetter && ($stayEndsOn === null || $stayEndsOn->lessThanOrEqualTo(CarbonImmutable::today()))) {
            throw new BusinessRuleViolation('RN-03', __('Enter the date your stay ends, as stated in the acceptance letter.'), 'stay_ends_on');
        }
    }
}
