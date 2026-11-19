<?php

namespace App\Modules\Inspections\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Inspections\Actions\CompleteInspection;
use App\Modules\Inspections\Actions\SaveInspectionAnswer;
use App\Modules\Inspections\Actions\StartInspection;
use App\Modules\Inspections\Enums\AnswerResult;
use App\Modules\Inspections\Enums\InspectionResult;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Http\Requests\CompleteInspectionRequest;
use App\Modules\Inspections\Http\Requests\InspectionAnswerRequest;
use App\Modules\Inspections\Models\ChecklistItem;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Presenters\InspectionPresenter;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-06.02 to HU-06.04: the inspector's agenda and checklist, built for a phone in the field.
 */
class InspectorController extends Controller
{
    public function __construct(private InspectionPresenter $presenter) {}

    public function index(Request $request): Response
    {
        /** @var User $inspector */
        $inspector = $request->user();
        $mine = Inspection::query()->where('inspector_id', $inspector->id)->with(['property.owner', 'inspector']);

        return Inertia::render('inspections/agenda', [
            'upcoming' => (clone $mine)->whereIn('status', [InspectionStatus::Assigned, InspectionStatus::InProgress])
                ->orderBy('scheduled_for')->get()->map(fn (Inspection $inspection): array => $this->presenter->summary($inspection)),
            'completed' => (clone $mine)->where('status', InspectionStatus::Completed)
                ->latest('completed_at')->limit(10)->get()->map(fn (Inspection $inspection): array => $this->presenter->summary($inspection)),
        ]);
    }

    public function show(Inspection $inspection): Response
    {
        return Inertia::render('inspections/perform', [
            'inspection' => $this->presenter->detail($inspection),
            'answerOptions' => AnswerResult::options(),
            'resultOptions' => InspectionResult::options(),
        ]);
    }

    public function start(Request $request, Inspection $inspection, StartInspection $start): RedirectResponse
    {
        Gate::authorize('perform', $inspection);
        $start->handle($this->inspector($request), $inspection);

        return back();
    }

    public function answer(InspectionAnswerRequest $request, Inspection $inspection, SaveInspectionAnswer $saveAnswer): RedirectResponse
    {
        Gate::authorize('perform', $inspection);

        /** @var list<UploadedFile> $photos */
        $photos = array_values(array_filter((array) $request->file('photos', []), fn ($file) => $file instanceof UploadedFile));

        $saveAnswer->handle(
            $this->inspector($request),
            $inspection,
            ChecklistItem::query()->findOrFail($request->integer('checklist_item_id')),
            $request->filled('unit_id') ? $inspection->property->units()->findOrFail($request->integer('unit_id')) : null,
            AnswerResult::from($request->string('result')->toString()),
            $request->input('comment'),
            $photos,
        );

        return back();
    }

    public function complete(CompleteInspectionRequest $request, Inspection $inspection, CompleteInspection $complete): RedirectResponse
    {
        Gate::authorize('perform', $inspection);

        $complete->handle(
            $this->inspector($request),
            $inspection,
            InspectionResult::from($request->string('result')->toString()),
            $request->input('observations'),
        );

        Toast::success(__('Inspection completed. The owner has been notified.'));

        return to_route('inspections.agenda');
    }

    private function inspector(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
