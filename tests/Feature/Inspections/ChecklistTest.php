<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Enums\ChecklistStatus;
use App\Modules\Inspections\Models\ChecklistTemplate;

/**
 * HU-03.03 · Plantillas de checklist versionadas (RN-82).
 */
it('seeds a published first version with critical and photo items', function () {
    $current = ChecklistTemplate::current();

    expect($current?->version)->toBe(1)
        ->and($current?->items()->where('is_critical', true)->exists())->toBeTrue()
        ->and($current?->items()->where('requires_photo', true)->exists())->toBeTrue();
});

it('edits a new version as a draft and publishes it, retiring the previous one', function () {
    $superAdmin = userWithRole(Role::SuperAdmin);
    $this->actingAs($superAdmin)->post(route('checklists.draft'))->assertSessionHasNoErrors();

    $draft = ChecklistTemplate::query()->where('status', ChecklistStatus::Draft)->sole();
    expect($draft->version)->toBe(2)->and($draft->items()->count())->toBe(ChecklistTemplate::current()?->items()->count());

    $this->actingAs($superAdmin)->post(route('checklists.items.store', $draft), [
        'category' => 'security', 'text' => 'Cámara de seguridad en la entrada', 'scope' => 'property', 'is_critical' => false, 'requires_photo' => true,
    ])->assertSessionHasNoErrors();

    $this->actingAs($superAdmin)->post(route('checklists.publish', $draft))->assertSessionHasNoErrors();

    expect(ChecklistTemplate::current()?->id)->toBe($draft->id)
        ->and(ChecklistTemplate::query()->where('version', 1)->value('status'))->toBe(ChecklistStatus::Retired);
    $this->assertDatabaseHas('activity_log', ['event' => 'checklist_published', 'subject_id' => $draft->id]);
});

it('never changes a published version', function () {
    $current = ChecklistTemplate::current();

    $this->actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('checklists.items.store', $current), ['category' => 'gas', 'text' => 'X', 'scope' => 'property'])
        ->assertSessionHasErrors('domain');
});

it('keeps inspections on the version they started with', function () {
    $inspection = propertyAwaitingInspection();
    $superAdmin = userWithRole(Role::SuperAdmin);

    $this->actingAs($superAdmin)->post(route('checklists.draft'));
    $draft = ChecklistTemplate::query()->where('status', ChecklistStatus::Draft)->sole();
    $this->actingAs($superAdmin)->post(route('checklists.publish', $draft));

    expect($inspection->fresh()?->checklist_template_id)->not->toBe($draft->id);
});

it('keeps the checklist editor away from administrators', function () {
    $this->actingAs(userWithRole(Role::Admin))->get(route('checklists.index'))->assertForbidden();
});
