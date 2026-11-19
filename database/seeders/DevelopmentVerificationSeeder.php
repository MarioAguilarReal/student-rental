<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Identity\Actions\AddAccountProfile;
use App\Modules\Identity\Enums\Role;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Applicants waiting in the review queue, with a placeholder PDF each. Local only.
 */
class DevelopmentVerificationSeeder extends Seeder
{
    private const APPLICANTS = [
        ['Mariana', 'López Ríos', 'mariana@example.test', Role::Client, CommunityType::Student, DocumentType::Credential, 4],
        ['Lukas', 'Becker', 'lukas@uni-example.de', Role::Client, CommunityType::ExchangeStudent, DocumentType::AcceptanceLetter, 2],
        ['Roberto', 'Salinas Ortega', 'roberto@example.test', Role::Client, CommunityType::Faculty, DocumentType::Credential, 1],
        ['Graciela', 'Nava Cruz', 'graciela@example.com', Role::Owner, null, DocumentType::OfficialId, 3],
    ];

    public function run(AddAccountProfile $addAccountProfile): void
    {
        $institution = Institution::query()->where('name', 'Universidad Demo')->firstOrFail();

        foreach (self::APPLICANTS as [$firstName, $lastName, $email, $role, $communityType, $documentType, $daysAgo]) {
            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email]);
            $addAccountProfile->handle($user, $role);

            $profile = $user->verificationProfiles()
                ->where('kind', $role === Role::Owner ? VerificationKind::Owner : VerificationKind::Client)
                ->sole();

            $profile->forceFill([
                'status' => VerificationStatus::InReview,
                'community_type' => $communityType,
                'institution_id' => $communityType ? $institution->id : null,
                'stay_ends_on' => $documentType === DocumentType::AcceptanceLetter ? now()->addMonths(5) : null,
                'submitted_at' => now()->subDays($daysAgo),
            ])->save();

            $path = "verification/{$profile->id}/documento-demo.pdf";
            Storage::disk('local')->put($path, $this->placeholderPdf("{$firstName} {$lastName}"));

            $profile->documents()->create([
                'type' => $documentType,
                'status' => DocumentStatus::Pending,
                'disk' => 'local',
                'path' => $path,
                'original_name' => 'documento-demo.pdf',
                'mime_type' => 'application/pdf',
                'size' => Storage::disk('local')->size($path),
            ]);
        }
    }

    private function placeholderPdf(string $name): string
    {
        $text = 'Documento de prueba - '.iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $stream = "BT /F1 16 Tf 30 70 Td ({$text}) Tj ET";

        return "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 420 144]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            .'4 0 obj<</Length '.strlen($stream).">>stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            ."trailer<</Root 1 0 R>>\n%%EOF\n";
    }
}
