<?php

namespace App\Modules\Inspections\Presenters;

use App\Modules\Inspections\Actions\RequiredAnswers;
use App\Modules\Inspections\Enums\AnswerResult;
use App\Modules\Inspections\Enums\InspectionResult;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Models\InspectionAnswer;
use App\Modules\Properties\Models\Property;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Inspections shaped for the admin queue, the inspector's checklist and the owner's editor.
 * Evidence and the proof of ownership are private: only short-lived signed URLs leave here.
 */
class InspectionPresenter
{
    public function __construct(private RequiredAnswers $requiredAnswers) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Inspection $inspection): array
    {
        $property = $inspection->property;

        return [
            'id' => $inspection->id,
            'type' => $inspection->type->value,
            'type_label' => $inspection->type->label(),
            'status' => $inspection->status->value,
            'status_label' => $inspection->status->label(),
            'result' => $inspection->result?->value,
            'result_label' => $inspection->result?->label(),
            'observations' => $inspection->observations,
            'scheduled_for' => $inspection->scheduled_for?->toDateString(),
            'completed_at' => $inspection->completed_at?->toDateString(),
            'valid_until' => $inspection->valid_until?->toDateString(),
            'inspector' => $inspection->inspector ? ['id' => $inspection->inspector->id, 'name' => $inspection->inspector->name] : null,
            'property' => [
                'id' => $property->id,
                'title' => $property->title,
                'location' => $property->approximateLocation(),
                'owner' => ['id' => $property->owner->id, 'name' => $property->owner->name, 'email' => $property->owner->email],
            ],
        ];
    }

    /**
     * The full checklist with answers, for the inspector (and read-only for admins).
     *
     * @return array<string, mixed>
     */
    public function detail(Inspection $inspection): array
    {
        $inspection->loadMissing(['property.owner', 'template.items', 'inspector', 'answers.media']);
        $property = $inspection->property;
        $answers = $inspection->answers->keyBy(fn (InspectionAnswer $answer): string => "{$answer->checklist_item_id}:{$answer->unit_id}");
        $proof = $property->getFirstMedia(Property::OWNERSHIP_PROOF);
        $sections = [];

        foreach ($this->requiredAnswers->handle($inspection) as ['item' => $item, 'unit' => $unit]) {
            $key = $unit ? "unit-{$unit->id}" : 'property';
            $sections[$key] ??= ['key' => $key, 'unit_id' => $unit?->id, 'title' => $unit?->name, 'items' => []];
            /** @var InspectionAnswer|null $answer */
            $answer = $answers->get("{$item->id}:{$unit?->id}");

            $sections[$key]['items'][] = [
                'id' => $item->id,
                'text' => $item->text,
                'category' => $item->category->label(),
                'is_critical' => $item->is_critical,
                'requires_photo' => $item->requires_photo,
                'answer' => $answer ? $this->answer($answer) : null,
            ];
        }

        $all = collect($sections)->flatMap(fn (array $section) => $section['items']);

        return [
            ...$this->summary($inspection),
            'address' => collect([
                trim("{$property->street} {$property->exterior_number}".($property->interior_number ? " int. {$property->interior_number}" : '')),
                $property->neighborhood,
                trim("{$property->postal_code} {$property->city}"),
                $property->state,
            ])->filter()->implode(', '),
            'map_url' => $property->latitude !== null ? "https://www.openstreetmap.org/?mlat={$property->latitude}&mlon={$property->longitude}#map=18/{$property->latitude}/{$property->longitude}" : null,
            'ownership_proof' => $proof ? ['name' => $proof->file_name, 'url' => $proof->getTemporaryUrl(now()->addMinutes(10))] : null,
            'template_version' => $inspection->template->version,
            'sections' => array_values($sections),
            'progress' => ['answered' => $all->whereNotNull('answer')->count(), 'total' => $all->count()],
            'has_critical_failure' => $all->contains(fn (array $item) => $item['is_critical'] && ($item['answer']['result'] ?? null) === AnswerResult::NonCompliant->value),
        ];
    }

    /**
     * The latest inspection of a property for the owner's editor, with what failed.
     *
     * @return array<string, mixed>|null
     */
    public function latestForProperty(Property $property): ?array
    {
        $inspection = Inspection::query()->where('property_id', $property->id)->with(['inspector', 'property.owner'])->latest('id')->first();

        if (! $inspection) {
            return null;
        }

        $failed = $inspection->answers()
            ->where('result', AnswerResult::NonCompliant)
            ->with(['item', 'media'])
            ->get()
            ->map(fn (InspectionAnswer $answer): array => [
                'id' => $answer->id,
                'text' => $answer->item->text,
                'unit' => $answer->unit_id ? $property->units()->whereKey($answer->unit_id)->value('name') : null,
                'is_critical' => $answer->item->is_critical,
                'comment' => $answer->comment,
                'photos' => $this->photos($answer),
            ]);

        return [
            ...$this->summary($inspection),
            'failed_items' => $inspection->result === InspectionResult::Approved ? [] : $failed->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function answer(InspectionAnswer $answer): array
    {
        return [
            'id' => $answer->id,
            'result' => $answer->result->value,
            'comment' => $answer->comment,
            'photos' => $this->photos($answer),
        ];
    }

    /**
     * @return list<array{id: int, url: string}>
     */
    private function photos(InspectionAnswer $answer): array
    {
        return array_values($answer->getMedia(InspectionAnswer::EVIDENCE)
            ->map(fn (Media $photo): array => ['id' => $photo->id, 'url' => $photo->getTemporaryUrl(now()->addMinutes(10))])
            ->all());
    }
}
