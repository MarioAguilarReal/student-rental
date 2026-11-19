<?php

namespace App\Modules\Verification\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;
use App\Modules\Shared\Enums\Concerns\HasTransitions;

/**
 * State machine in docs/spec/04-modelo-dominio.md ("Verificación").
 */
enum VerificationStatus: string
{
    use HasLabel, HasTransitions;

    case Unverified = 'unverified';
    case EmailVerified = 'email_verified';
    case InReview = 'in_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Unverified => [self::EmailVerified],
            self::EmailVerified, self::Rejected, self::Expired => [self::InReview],
            self::InReview => [self::Verified, self::Rejected],
            self::Verified => [self::Expired],
        };
    }

    public function acceptsSubmissions(): bool
    {
        return $this->canTransitionTo(self::InReview);
    }
}
