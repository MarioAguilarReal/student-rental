<?php

namespace App\Modules\Inspections\Actions;

use App\Models\User;
use App\Modules\Inspections\Enums\ChecklistCategory;
use App\Modules\Inspections\Enums\ChecklistStatus;
use App\Modules\Inspections\Enums\ItemScope;
use App\Modules\Inspections\Events\ChecklistPublished;
use App\Modules\Inspections\Models\ChecklistItem;
use App\Modules\Inspections\Models\ChecklistTemplate;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-03.03 · RN-82: checklists are versioned. Only a draft can be edited; publishing makes it the
 * version new inspections use and retires the previous one. Inspections already created keep theirs.
 */
class ManageChecklist
{
    /**
     * Opens a draft copied from the version in force (or returns the existing draft).
     */
    public function handle(): ChecklistTemplate
    {
        $draft = ChecklistTemplate::query()->where('status', ChecklistStatus::Draft)->first();

        if ($draft) {
            return $draft;
        }

        return DB::transaction(function () {
            $current = ChecklistTemplate::current();
            $draft = ChecklistTemplate::create([
                'name' => $current->name ?? Text::of('Safety and listing checklist'),
                'version' => (int) ChecklistTemplate::query()->max('version') + 1,
                'status' => ChecklistStatus::Draft,
            ]);

            foreach ($current->items ?? [] as $item) {
                $draft->items()->create($item->only(['category', 'text', 'scope', 'is_critical', 'requires_photo', 'position']));
            }

            return $draft;
        });
    }

    public function saveItem(ChecklistTemplate $draft, ?ChecklistItem $item, ChecklistCategory $category, string $text, ItemScope $scope, bool $critical, bool $requiresPhoto): ChecklistItem
    {
        $this->ensureDraft($draft);

        $item ??= new ChecklistItem(['checklist_template_id' => $draft->id, 'position' => $draft->items()->count()]);
        $item->fill(['category' => $category, 'text' => $text, 'scope' => $scope, 'is_critical' => $critical, 'requires_photo' => $requiresPhoto])->save();

        return $item;
    }

    public function removeItem(ChecklistTemplate $draft, ChecklistItem $item): void
    {
        $this->ensureDraft($draft);
        $item->delete();
    }

    public function publish(User $actor, ChecklistTemplate $draft): ChecklistTemplate
    {
        $this->ensureDraft($draft);

        if (! $draft->items()->exists()) {
            throw new BusinessRuleViolation('RN-82', Text::of('A checklist needs at least one item.'));
        }

        DB::transaction(function () use ($draft) {
            ChecklistTemplate::query()->where('status', ChecklistStatus::Published)->update(['status' => ChecklistStatus::Retired]);
            $draft->forceFill(['status' => ChecklistStatus::Published, 'published_at' => now()])->save();
        });

        ChecklistPublished::dispatch($draft, $actor);

        return $draft;
    }

    private function ensureDraft(ChecklistTemplate $template): void
    {
        if ($template->status !== ChecklistStatus::Draft) {
            throw new BusinessRuleViolation('RN-82', Text::of('Published checklists cannot be changed. Create a new version.'));
        }
    }
}
