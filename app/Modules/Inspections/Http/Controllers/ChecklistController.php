<?php

namespace App\Modules\Inspections\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Inspections\Actions\ManageChecklist;
use App\Modules\Inspections\Enums\ChecklistCategory;
use App\Modules\Inspections\Enums\ChecklistStatus;
use App\Modules\Inspections\Enums\ItemScope;
use App\Modules\Inspections\Http\Requests\ChecklistItemRequest;
use App\Modules\Inspections\Models\ChecklistItem;
use App\Modules\Inspections\Models\ChecklistTemplate;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-03.03: versioned checklists for Super Admins.
 */
class ChecklistController extends Controller
{
    public function index(): Response
    {
        $shape = fn (ChecklistTemplate $template): array => [
            'id' => $template->id,
            'name' => $template->name,
            'version' => $template->version,
            'status' => $template->status->value,
            'status_label' => $template->status->label(),
            'published_at' => $template->published_at?->toDateString(),
            'items' => $template->items->map(fn (ChecklistItem $item): array => [
                'id' => $item->id,
                'category' => $item->category->value,
                'category_label' => $item->category->label(),
                'text' => $item->text,
                'scope' => $item->scope->value,
                'scope_label' => $item->scope->label(),
                'is_critical' => $item->is_critical,
                'requires_photo' => $item->requires_photo,
            ]),
        ];

        $draft = ChecklistTemplate::query()->where('status', ChecklistStatus::Draft)->with('items')->first();
        $current = ChecklistTemplate::current()?->load('items');

        return Inertia::render('inspections/checklists', [
            'current' => $current ? $shape($current) : null,
            'draft' => $draft ? $shape($draft) : null,
            'history' => ChecklistTemplate::query()->where('status', ChecklistStatus::Retired)->latest('version')->get(['id', 'version', 'published_at']),
            'categories' => ChecklistCategory::options(),
            'scopes' => ItemScope::options(),
        ]);
    }

    public function draft(ManageChecklist $manage): RedirectResponse
    {
        $manage->handle();

        return back();
    }

    public function storeItem(ChecklistItemRequest $request, ChecklistTemplate $template, ManageChecklist $manage): RedirectResponse
    {
        $this->saveItem($request, $template, null, $manage);

        return back();
    }

    public function updateItem(ChecklistItemRequest $request, ChecklistTemplate $template, ChecklistItem $item, ManageChecklist $manage): RedirectResponse
    {
        $this->saveItem($request, $template, $item, $manage);

        return back();
    }

    public function destroyItem(ChecklistTemplate $template, ChecklistItem $item, ManageChecklist $manage): RedirectResponse
    {
        $manage->removeItem($template, $item);

        return back();
    }

    public function publish(Request $request, ChecklistTemplate $template, ManageChecklist $manage): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $manage->publish($actor, $template);
        Toast::success(__('Version :version published. New inspections will use it.', ['version' => $template->version]));

        return back();
    }

    private function saveItem(ChecklistItemRequest $request, ChecklistTemplate $template, ?ChecklistItem $item, ManageChecklist $manage): void
    {
        $manage->saveItem(
            $template,
            $item,
            ChecklistCategory::from($request->string('category')->toString()),
            $request->string('text')->toString(),
            ItemScope::from($request->string('scope')->toString()),
            $request->boolean('is_critical'),
            $request->boolean('requires_photo'),
        );
    }
}
