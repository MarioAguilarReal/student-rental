<?php

namespace App\Modules\Inspections\Actions;

use App\Models\User;
use App\Modules\Inspections\Enums\AnswerResult;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Enums\ItemScope;
use App\Modules\Inspections\Models\ChecklistItem;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Models\InspectionAnswer;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * HU-06.03: answers one item (for the shared areas or one space) with a comment and photos.
 * Progress is saved as it goes.
 */
class SaveInspectionAnswer
{
    /**
     * @param  list<UploadedFile>  $photos
     */
    public function handle(User $inspector, Inspection $inspection, ChecklistItem $item, ?Unit $unit, AnswerResult $result, ?string $comment, array $photos = []): InspectionAnswer
    {
        EnsureAssignedInspector::check($inspector, $inspection, InspectionStatus::InProgress);

        if ($item->checklist_template_id !== $inspection->checklist_template_id) {
            throw new BusinessRuleViolation('RN-82', Text::of('That item is not part of this checklist.'));
        }

        if ($item->scope === ItemScope::Property && $unit !== null) {
            throw new BusinessRuleViolation('RN-87', Text::of('This item is answered for the whole property.'), 'unit_id');
        }

        if ($item->scope === ItemScope::Unit && ($unit === null || $unit->property_id !== $inspection->property_id || ! $unit->is_rentable)) {
            throw new BusinessRuleViolation('RN-87', Text::of('Choose one of the rentable spaces of this property.'), 'unit_id');
        }

        return DB::transaction(function () use ($inspection, $item, $unit, $result, $comment, $photos) {
            $answer = InspectionAnswer::query()->updateOrCreate(
                ['inspection_id' => $inspection->id, 'checklist_item_id' => $item->id, 'unit_id' => $unit?->id],
                ['result' => $result, 'comment' => $comment],
            );

            foreach ($photos as $photo) {
                $answer->addMedia($photo)->toMediaCollection(InspectionAnswer::EVIDENCE);
            }

            return $answer;
        });
    }
}
