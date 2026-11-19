<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Notifications\Messages\VerificationMessage;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationStatus;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * HU-02.04 · Revisar documentos (RN-04, RN-08).
 */
beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->freezeTime();
});

function profileInReview(CommunityType $communityType = CommunityType::Student, DocumentType $document = DocumentType::Credential, array $attributes = []): VerificationProfile
{
    $profile = VerificationProfile::factory()->inReview($communityType)->create($attributes);
    $profile->documents()->create([
        'type' => $document,
        'status' => DocumentStatus::Pending,
        'disk' => 'local',
        'path' => UploadedFile::fake()->image('doc.jpg')->store("verification/{$profile->id}", 'local'),
        'original_name' => 'doc.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);

    return $profile;
}

it('lists submissions oldest first and filters by profile type', function () {
    $newer = profileInReview(attributes: ['submitted_at' => now()->subDay()]);
    $older = profileInReview(attributes: ['submitted_at' => now()->subDays(3)]);
    VerificationProfile::factory()->owner()->inReview()->create(['submitted_at' => now()->subDays(2)]);
    VerificationProfile::factory()->create(); // not submitted

    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->get(route('verifications.reviews.index'))
        ->assertInertia(fn ($page) => $page
            ->has('profiles.data', 3)
            ->where('profiles.data.0.id', $older->id));

    $this->actingAs($admin)->get(route('verifications.reviews.index', ['kind' => 'client']))
        ->assertInertia(fn ($page) => $page
            ->has('profiles.data', 2)
            ->where('profiles.data.1.id', $newer->id));
});

it('shows documents through short-lived private links', function () {
    $profile = profileInReview();

    $this->actingAs(userWithRole(Role::Admin))
        ->get(route('verifications.reviews.show', $profile))
        ->assertInertia(fn ($page) => $page
            ->where('canDecide', true)
            ->where('profile.documents.0.url', fn (string $url) => str_contains($url, 'expir')));
});

it('sets the validity by community type when approving', function (CommunityType $type, DocumentType $document, ?string $stayEndsOn, string $expected) {
    $profile = profileInReview($type, $document, ['stay_ends_on' => $stayEndsOn]);
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)
        ->post(route('verifications.reviews.approve', $profile))
        ->assertRedirect(route('verifications.reviews.index'));

    $profile->refresh();

    expect($profile->status)->toBe(VerificationStatus::Verified)
        ->and($profile->verified_until?->toDateString())->toBe($expected)
        ->and($profile->reviewed_by)->toBe($admin->id)
        ->and($profile->documents()->first()?->status)->toBe(DocumentStatus::Approved);
})->with([
    'student: 6 months' => [CommunityType::Student, DocumentType::Credential, null, now()->addMonthsNoOverflow(6)->toDateString()],
    'faculty: 12 months' => [CommunityType::Faculty, DocumentType::Credential, null, now()->addMonthsNoOverflow(12)->toDateString()],
    'exchange with letter: end of stay' => [CommunityType::ExchangeStudent, DocumentType::AcceptanceLetter, now()->addMonths(4)->toDateString(), now()->addMonths(4)->toDateString()],
    'exchange with letter: capped at 12 months' => [CommunityType::ExchangeStudent, DocumentType::AcceptanceLetter, now()->addMonths(18)->toDateString(), now()->addMonthsNoOverflow(12)->toDateString()],
    'exchange with credential: 6 months' => [CommunityType::ExchangeStudent, DocumentType::Credential, null, now()->addMonthsNoOverflow(6)->toDateString()],
]);

it('approves owners without an expiry date', function () {
    $profile = VerificationProfile::factory()->owner()->inReview()->create();

    $this->actingAs(userWithRole(Role::Admin))->post(route('verifications.reviews.approve', $profile));

    expect($profile->fresh()?->status)->toBe(VerificationStatus::Verified)
        ->and($profile->fresh()?->verified_until)->toBeNull();
});

it('notifies the applicant and records the decision', function () {
    $profile = profileInReview();
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->post(route('verifications.reviews.approve', $profile));

    Notification::assertSentTo($profile->user, VerificationMessage::class, fn (VerificationMessage $message) => $message->type === 'verification.approved');
    $this->assertDatabaseHas('activity_log', ['event' => 'approved', 'subject_id' => $profile->id, 'causer_id' => $admin->id]);
});

it('rejects with a mandatory reason that the applicant receives', function () {
    $profile = profileInReview();
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)
        ->post(route('verifications.reviews.reject', $profile), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    $this->actingAs($admin)
        ->post(route('verifications.reviews.reject', $profile), ['reason' => 'La credencial está vencida.'])
        ->assertRedirect(route('verifications.reviews.index'));

    $profile->refresh();

    expect($profile->status)->toBe(VerificationStatus::Rejected)
        ->and($profile->rejection_reason)->toBe('La credencial está vencida.')
        ->and($profile->documents()->first()?->status)->toBe(DocumentStatus::Rejected);
    Notification::assertSentTo($profile->user, VerificationMessage::class, fn (VerificationMessage $message) => $message->type === 'verification.rejected');
});

it('does not let an administrator review their own verification (RN-08)', function () {
    $admin = userWithRole(Role::Admin);
    $profile = profileInReview(attributes: ['user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('verifications.reviews.approve', $profile))
        ->assertSessionHasErrors('domain');

    expect($profile->fresh()?->status)->toBe(VerificationStatus::InReview);
});

it('cannot approve a profile that is not in review', function () {
    $profile = VerificationProfile::factory()->create();

    $this->actingAs(userWithRole(Role::Admin))
        ->post(route('verifications.reviews.approve', $profile))
        ->assertSessionHasErrors('status');
});

it('keeps the review queue away from non-admins', function (Role $role) {
    $this->actingAs(userWithRole($role))->get(route('verifications.reviews.index'))->assertForbidden();
})->with([Role::Client, Role::Owner, Role::Inspector]);
