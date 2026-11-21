<?php

use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Actions\RequiredAnswers;
use App\Modules\Inspections\Enums\InspectionResult;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Models\InspectionAnswer;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Properties\Enums\PropertyStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * HU-06.03 · Ejecutar checklist, HU-06.04 · Dictamen (RN-83, RN-84, RN-87) y HU-06.05 · Resultado.
 */
beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->inspection = propertyAwaitingInspection();
    $this->inspector = userWithRole(Role::Inspector);
    $this->admin = userWithRole(Role::Admin);
    $this->inspection->forceFill(['status' => InspectionStatus::Assigned, 'inspector_id' => $this->inspector->id, 'scheduled_for' => now()->toDateString()])->save();
});

/**
 * Answers every required item; $override decides the result for a given item.
 */
function answerAll(Inspection $inspection, User $inspector, ?callable $override = null): void
{
    foreach (app(RequiredAnswers::class)->handle($inspection) as ['item' => $item, 'unit' => $unit]) {
        $result = $override ? $override($item) : 'compliant';

        test()->actingAs($inspector)->post(route('inspections.answers.store', $inspection), [
            'checklist_item_id' => $item->id,
            'unit_id' => $unit?->id,
            'result' => $result,
            'comment' => $result === 'non_compliant' ? 'No cumple' : null,
            'photos' => $item->requires_photo && $result !== 'not_applicable' ? [UploadedFile::fake()->image('evidencia.jpg')] : [],
        ])->assertSessionHasNoErrors();
    }
}

it('starts, answers with evidence and approves; the property becomes approved for 12 months', function () {
    $this->actingAs($this->inspector)->post(route('inspections.start', $this->inspection))->assertSessionHasNoErrors();
    answerAll($this->inspection, $this->inspector);

    $this->actingAs($this->inspector)
        ->post(route('inspections.complete', $this->inspection), ['result' => 'approved'])
        ->assertRedirect(route('inspections.agenda'));

    $this->inspection->refresh();
    $property = $this->inspection->property->fresh();

    expect($this->inspection->status)->toBe(InspectionStatus::Completed)
        ->and($this->inspection->result)->toBe(InspectionResult::Approved)
        ->and($property?->status)->toBe(PropertyStatus::Approved)
        ->and($property?->inspection_valid_until?->toDateString())->toBe(now()->addMonthsNoOverflow(12)->toDateString())
        ->and(InspectionAnswer::query()->whereHas('media')->exists())->toBeTrue();
    Notification::assertSentTo($property->owner, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'inspection.approved');
    Notification::assertSentTo($this->admin, PlatformMessage::class, fn (PlatformMessage $message) => $message->type === 'inspection.approved');
});

it('answers unit items once per rentable space (RN-87)', function () {
    $this->actingAs($this->inspector)->post(route('inspections.start', $this->inspection));
    $template = $this->inspection->template;
    $required = app(RequiredAnswers::class)->handle($this->inspection);

    $propertyItems = $template->items->where('scope.value', 'property')->count();
    $unitItems = $template->items->where('scope.value', 'unit')->count();

    expect(count($required))->toBe($propertyItems + 2 * $unitItems);
});

it('does not complete with unanswered items or missing photos (RN-84, RN-87)', function () {
    $this->actingAs($this->inspector)->post(route('inspections.start', $this->inspection));

    $this->actingAs($this->inspector)->post(route('inspections.complete', $this->inspection), ['result' => 'approved'])->assertSessionHasErrors('result');

    $photoItem = $this->inspection->template->items->firstWhere('requires_photo', true);
    answerAll($this->inspection, $this->inspector);
    InspectionAnswer::query()->where('checklist_item_id', $photoItem->id)->sole()->clearMediaCollection(InspectionAnswer::EVIDENCE);

    $this->actingAs($this->inspector)->post(route('inspections.complete', $this->inspection), ['result' => 'approved'])->assertSessionHasErrors('result');
});

it('forces a rejection when a critical item fails, and shows the owner what failed (RN-83, HU-06.05)', function () {
    $this->actingAs($this->inspector)->post(route('inspections.start', $this->inspection));
    $critical = $this->inspection->template->items->firstWhere('is_critical', true);
    answerAll($this->inspection, $this->inspector, fn ($item) => $item->id === $critical->id ? 'non_compliant' : 'compliant');

    $this->actingAs($this->inspector)
        ->post(route('inspections.complete', $this->inspection), ['result' => 'approved', 'observations' => 'Falta extintor'])
        ->assertSessionHasNoErrors();

    $property = $this->inspection->property->fresh();
    expect($this->inspection->fresh()?->result)->toBe(InspectionResult::Rejected)
        ->and($property?->status)->toBe(PropertyStatus::Rejected);

    $this->actingAs($property->owner)->get(route('properties.edit', $property))
        ->assertInertia(fn ($page) => $page
            ->where('inspection.result', 'rejected')
            ->where('inspection.observations', 'Falta extintor')
            ->has('inspection.failed_items', 1)
            ->where('inspection.failed_items.0.is_critical', true));
});

it('requires observations unless the result is a clean approval', function () {
    $this->actingAs($this->inspector)->post(route('inspections.start', $this->inspection));
    answerAll($this->inspection, $this->inspector);

    $this->actingAs($this->inspector)
        ->post(route('inspections.complete', $this->inspection), ['result' => 'approved_with_observations'])
        ->assertSessionHasErrors('observations');
});

it('only lets the assigned inspector work on it', function () {
    $other = userWithRole(Role::Inspector);

    $this->actingAs($other)->get(route('inspections.perform', $this->inspection))->assertForbidden();
    $this->actingAs($other)->post(route('inspections.start', $this->inspection))->assertForbidden();
});

it('refuses answers before starting and items from another checklist', function () {
    $item = $this->inspection->template->items->first();

    $this->actingAs($this->inspector)->post(route('inspections.answers.store', $this->inspection), [
        'checklist_item_id' => $item->id, 'result' => 'compliant',
    ])->assertSessionHasErrors('status');
});

it('refuses a unit item without a space of this property', function () {
    $this->actingAs($this->inspector)->post(route('inspections.start', $this->inspection));
    $unitItem = $this->inspection->template->items->first(fn ($item) => $item->scope->value === 'unit');

    $this->actingAs($this->inspector)->post(route('inspections.answers.store', $this->inspection), [
        'checklist_item_id' => $unitItem->id, 'result' => 'compliant',
    ])->assertSessionHasErrors('unit_id');
});
