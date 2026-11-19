<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationProfile>
 */
class VerificationProfileFactory extends Factory
{
    protected $model = VerificationProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => VerificationKind::Client,
            'status' => VerificationStatus::EmailVerified,
        ];
    }

    public function owner(): static
    {
        return $this->state(['kind' => VerificationKind::Owner]);
    }

    public function inReview(CommunityType $communityType = CommunityType::Student): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VerificationStatus::InReview,
            'community_type' => $attributes['kind'] === VerificationKind::Owner ? null : $communityType,
            'institution_id' => $attributes['kind'] === VerificationKind::Owner ? null : Institution::factory(),
            'submitted_at' => now(),
        ]);
    }

    public function verified(?string $until = '+6 months'): static
    {
        return $this->state([
            'status' => VerificationStatus::Verified,
            'community_type' => CommunityType::Student,
            'institution_id' => Institution::factory(),
            'submitted_at' => now()->subWeek(),
            'reviewed_at' => now()->subDays(6),
            'verified_until' => $until ? now()->modify($until)->toDateString() : null,
        ]);
    }
}
