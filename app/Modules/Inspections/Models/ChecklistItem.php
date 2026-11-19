<?php

namespace App\Modules\Inspections\Models;

use App\Modules\Inspections\Enums\ChecklistCategory;
use App\Modules\Inspections\Enums\ItemScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $checklist_template_id
 * @property ChecklistCategory $category
 * @property string $text
 * @property ItemScope $scope
 * @property bool $is_critical
 * @property bool $requires_photo
 * @property int $position
 */
class ChecklistItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'category' => ChecklistCategory::class,
            'scope' => ItemScope::class,
            'is_critical' => 'boolean',
            'requires_photo' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }
}
