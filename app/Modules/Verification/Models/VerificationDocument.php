<?php

namespace App\Modules\Verification\Models;

use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Stored on a private disk; only reachable through short-lived signed URLs (RN-07).
 *
 * @property int $id
 * @property DocumentType $type
 * @property DocumentStatus $status
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 */
class VerificationDocument extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<VerificationProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(VerificationProfile::class, 'verification_profile_id');
    }

    public function temporaryUrl(int $minutes = 5): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes($minutes));
    }
}
