<?php

namespace App\Modules\Inspections\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Inspections\Actions\AssignInspection;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Http\Requests\AssignInspectionRequest;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Inspections\Presenters\InspectionPresenter;
use App\Modules\Shared\Http\Toast;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-06.01: the administrators' inspection queue.
 */
class InspectionQueueController extends Controller
{
    public function __construct(private InspectionPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $status = InspectionStatus::tryFrom($request->string('status')->toString()) ?? InspectionStatus::PendingAssignment;
        $counts = Inspection::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $inspections = Inspection::query()
            ->with(['property.owner', 'inspector'])
            ->where('status', $status)
            ->when($status === InspectionStatus::Completed, fn ($query) => $query->latest('completed_at'), fn ($query) => $query->orderByRaw('scheduled_for nulls first')->oldest('id'))
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Inspection $inspection): array => $this->presenter->summary($inspection));

        return Inertia::render('inspections/queue/index', [
            'inspections' => $inspections,
            'status' => $status->value,
            'statusCounts' => collect(InspectionStatus::cases())->mapWithKeys(fn (InspectionStatus $case) => [$case->value => (int) ($counts[$case->value] ?? 0)]),
            'statusOptions' => InspectionStatus::options(),
        ]);
    }

    public function show(Inspection $inspection): Response
    {
        return Inertia::render('inspections/queue/show', [
            'inspection' => $this->presenter->detail($inspection),
            'inspectors' => User::role(Role::Inspector->value)
                ->whereNull('suspended_at')
                ->whereKeyNot($inspection->property->owner_id)
                ->orderBy('last_name')
                ->get()
                ->map(fn (User $inspector): array => ['value' => $inspector->id, 'label' => $inspector->name]),
            'canAssign' => $inspection->status->canTransitionTo(InspectionStatus::Assigned),
        ]);
    }

    public function assign(AssignInspectionRequest $request, Inspection $inspection, AssignInspection $assign): RedirectResponse
    {
        Gate::authorize('assign', $inspection);

        /** @var User $actor */
        $actor = $request->user();
        $assign->handle(
            $actor,
            $inspection,
            User::query()->findOrFail($request->integer('inspector_id')),
            CarbonImmutable::parse($request->string('scheduled_for')->toString()),
        );

        Toast::success(__('Inspection assigned. The inspector and the owner have been notified.'));

        return back();
    }
}
