<?php

namespace App\Modules\Inspections\Actions;

use App\Models\User;
use App\Modules\Inspections\Enums\AnswerResult;
use App\Modules\Inspections\Enums\InspectionResult;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Events\InspectionCompleted;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Models\InspectionAnswer;
use App\Modules\Properties\Actions\RecordInspectionOutcome;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Parameters;
use App\Modules\Shared\Support\Text;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-06.04: the verdict. Every required item answered (RN-87); items that require a photo have
 * one unless they do not apply (RN-84); any critical item that fails forces a rejection (RN-83);
 * observations are mandatory unless the result is a clean approval. The verdict is final (D-24).
 */
class CompleteInspection
{
    public function __construct(
        private RequiredAnswers $requiredAnswers,
        private RecordInspectionOutcome $recordOutcome,
    ) {}

    public function handle(User $inspector, Inspection $inspection, InspectionResult $requested, ?string $observations): Inspection
    {
        EnsureAssignedInspector::check($inspector, $inspection, InspectionStatus::InProgress);

        $answers = $inspection->answers()->with(['item', 'media'])->get()
            ->keyBy(fn (InspectionAnswer $answer): string => "{$answer->checklist_item_id}:{$answer->unit_id}");

        $missing = 0;
        $withoutPhoto = 0;
        $criticalFailure = false;

        foreach ($this->requiredAnswers->handle($inspection) as ['item' => $item, 'unit' => $unit]) {
            /** @var InspectionAnswer|null $answer */
            $answer = $answers->get("{$item->id}:{$unit?->id}");

            if (! $answer) {
                $missing++;

                continue;
            }

            if ($item->requires_photo && $answer->result !== AnswerResult::NotApplicable && $answer->getMedia(InspectionAnswer::EVIDENCE)->isEmpty()) {
                $withoutPhoto++;
            }

            $criticalFailure = $criticalFailure || ($item->is_critical && $answer->result === AnswerResult::NonCompliant);
        }

        if ($missing > 0) {
            throw new BusinessRuleViolation('RN-87', Text::of(':count items are still unanswered.', ['count' => $missing]), 'result');
        }

        if ($withoutPhoto > 0) {
            throw new BusinessRuleViolation('RN-84', Text::of(':count items need at least one photo.', ['count' => $withoutPhoto]), 'result');
        }

        $result = $criticalFailure ? InspectionResult::Rejected : $requested;

        if ($result !== InspectionResult::Approved && blank($observations)) {
            throw new BusinessRuleViolation('HU-06.04', Text::of('Write the observations the owner must read.'), 'observations');
        }

        DB::transaction(function () use ($inspection, $result, $observations) {
            $validUntil = $result->approves() ? CarbonImmutable::today()->addMonthsNoOverflow(Parameters::int(SystemParameter::InspectionValidityMonths)) : null;

            $inspection->forceFill([
                'status' => InspectionStatus::Completed,
                'result' => $result,
                'observations' => $observations,
                'completed_at' => now(),
                'valid_until' => $validUntil,
            ])->save();

            $this->recordOutcome->handle($inspection->property, $result->approves(), $validUntil);
        });

        InspectionCompleted::dispatch($inspection);

        return $inspection;
    }
}
