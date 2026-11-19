<?php

use App\Models\User;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Identity\Enums\Role;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * HU-02.02 (clients, RN-03) and HU-02.03 (owners, RN-06) · Subir documentos.
 */
beforeEach(fn () => Storage::fake('local'));

function clientProfile(User $user): VerificationProfile
{
    return $user->verificationProfiles()->where('kind', VerificationKind::Client)->sole();
}

it('sends a student credential for review and stores it privately', function () {
    $user = userWithRole(Role::Client);
    $institution = Institution::factory()->create();

    $this->actingAs($user)->post(route('verifications.client.store'), [
        'community_type' => 'student',
        'institution_id' => $institution->id,
        'credential' => UploadedFile::fake()->image('credencial.jpg'),
    ])->assertSessionHasNoErrors();

    $profile = clientProfile($user);
    $document = $profile->documents()->sole();

    expect($profile->status)->toBe(VerificationStatus::InReview)
        ->and($profile->community_type)->toBe(CommunityType::Student)
        ->and($profile->institution_id)->toBe($institution->id)
        ->and($profile->submitted_at)->not->toBeNull()
        ->and($document->type)->toBe(DocumentType::Credential)
        ->and($document->disk)->toBe('local');
    Storage::disk('local')->assertExists($document->path);
    $this->assertDatabaseHas('activity_log', ['event' => 'submitted', 'subject_id' => $profile->id]);
});

it('requires a credential from faculty and students', function (string $communityType) {
    $user = userWithRole(Role::Client);

    $this->actingAs($user)->post(route('verifications.client.store'), [
        'community_type' => $communityType,
        'institution_id' => Institution::factory()->create()->id,
    ])->assertSessionHasErrors('credential');

    expect(clientProfile($user)->status)->toBe(VerificationStatus::EmailVerified);
})->with(['faculty', 'student']);

it('accepts an acceptance letter from exchange students, with the end of their stay', function () {
    $user = userWithRole(Role::Client);

    $this->actingAs($user)->post(route('verifications.client.store'), [
        'community_type' => 'exchange_student',
        'institution_id' => Institution::factory()->create()->id,
        'acceptance_letter' => UploadedFile::fake()->create('carta.pdf', 200, 'application/pdf'),
        'stay_ends_on' => now()->addMonths(5)->toDateString(),
    ])->assertSessionHasNoErrors();

    expect(clientProfile($user)->stay_ends_on?->toDateString())->toBe(now()->addMonths(5)->toDateString());
});

it('requires the end of stay when an acceptance letter is sent', function () {
    $this->actingAs(userWithRole(Role::Client))->post(route('verifications.client.store'), [
        'community_type' => 'exchange_student',
        'institution_id' => Institution::factory()->create()->id,
        'acceptance_letter' => UploadedFile::fake()->create('carta.pdf', 200, 'application/pdf'),
    ])->assertSessionHasErrors('stay_ends_on');
});

it('rejects files that are not images or PDFs, or bigger than 10 MB', function (UploadedFile $file) {
    $this->actingAs(userWithRole(Role::Client))->post(route('verifications.client.store'), [
        'community_type' => 'student',
        'institution_id' => Institution::factory()->create()->id,
        'credential' => $file,
    ])->assertSessionHasErrors('credential');
})->with([
    'executable' => fn () => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
    'too big' => fn () => UploadedFile::fake()->create('scan.pdf', 10_241, 'application/pdf'),
]);

it('does not accept a new submission while one is in review', function () {
    $user = userWithRole(Role::Client);
    clientProfile($user)->update(['status' => VerificationStatus::InReview]);

    $this->actingAs($user)->post(route('verifications.client.store'), [
        'community_type' => 'student',
        'institution_id' => Institution::factory()->create()->id,
        'credential' => UploadedFile::fake()->image('credencial.jpg'),
    ])->assertSessionHasErrors('status');
});

it('lets a rejected client send documents again', function () {
    $user = userWithRole(Role::Client);
    clientProfile($user)->update(['status' => VerificationStatus::Rejected, 'rejection_reason' => 'Ilegible']);

    $this->actingAs($user)->post(route('verifications.client.store'), [
        'community_type' => 'student',
        'institution_id' => Institution::factory()->create()->id,
        'credential' => UploadedFile::fake()->image('credencial.jpg'),
    ])->assertSessionHasNoErrors();

    expect(clientProfile($user)->status)->toBe(VerificationStatus::InReview)
        ->and(clientProfile($user)->rejection_reason)->toBeNull();
});

it('sends an owner official ID for review', function () {
    $owner = userWithRole(Role::Owner);

    $this->actingAs($owner)->post(route('verifications.owner.store'), [
        'official_id' => UploadedFile::fake()->image('ine.png'),
    ])->assertSessionHasNoErrors();

    $profile = $owner->verificationProfiles()->where('kind', VerificationKind::Owner)->sole();

    expect($profile->status)->toBe(VerificationStatus::InReview)
        ->and($profile->documents()->sole()->type)->toBe(DocumentType::OfficialId);
});

it('shows the verification page with the user profiles', function () {
    $this->actingAs(userWithRole(Role::Owner))
        ->get(route('verifications.show'))
        ->assertInertia(fn ($page) => $page
            ->component('verification/show')
            ->has('profiles', 1)
            ->where('profiles.0.kind', 'owner')
            ->has('missingProfiles', 1));
});

it('forbids staff without a profile from the verification page', function () {
    $this->actingAs(userWithRole(Role::Inspector))->get(route('verifications.show'))->assertForbidden();
});

it('never serves private documents without a valid signature (RN-07)', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->get('/storage/verification/1/credencial.jpg')
        ->assertForbidden();
});
