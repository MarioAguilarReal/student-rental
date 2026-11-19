<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\VerificationProfileFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property VerificationKind $kind
 * @property VerificationStatus $status
 * @property CommunityType|null $community_type
 * @property int|null $institution_id
 * @property CarbonImmutable|null $stay_ends_on
 * @property CarbonImmutable|null $submitted_at
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $verified_until
 * @property CarbonImmutable|null $expiry_reminder_sent_at
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read Institution|null $institution
 */
#[UseFactory(VerificationProfileFactory::class)]
class VerificationProfile extends Model
{
    /** @use HasFactory<VerificationProfileFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kind' => VerificationKind::class,
            'status' => VerificationStatus::class,
            'community_type' => CommunityType::class,
            'stay_ends_on' => 'immutable_date',
            'submitted_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'verified_until' => 'immutable_date',
            'expiry_reminder_sent_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return HasMany<VerificationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class)->latest('id');
    }

    /**
     * RN-01: verified and not past its validity date.
     */
    public function isVerified(): bool
    {
        return $this->status === VerificationStatus::Verified
            && ($this->verified_until === null || $this->verified_until->greaterThanOrEqualTo(CarbonImmutable::today()));
    }
}
