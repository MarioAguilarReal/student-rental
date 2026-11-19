<?php

namespace App\Modules\Verification\Actions;

use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Models\VerificationDocument;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\UploadedFile;

/**
 * RN-07: documents go to the private disk, never to a public one.
 */
class StoreVerificationDocument
{
    public const DISK = 'local';

    public function handle(VerificationProfile $profile, DocumentType $type, UploadedFile $file): VerificationDocument
    {
        $path = $file->store("verification/{$profile->id}", self::DISK);

        return $profile->documents()->create([
            'type' => $type,
            'status' => DocumentStatus::Pending,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
    }
}
