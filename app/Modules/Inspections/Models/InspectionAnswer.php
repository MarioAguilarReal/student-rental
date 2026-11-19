<?php

namespace App\Modules\Inspections\Models;

use App\Modules\Inspections\Enums\AnswerResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One checklist item answered, for the shared areas or for one rentable space (RN-87).
 * Evidence photos are private (RN-07): only the owner, the inspector and admins see them.
 *
 * @property int $id
 * @property int $inspection_id
 * @property int $checklist_item_id
 * @property int|null $unit_id
 * @property AnswerResult $result
 * @property string|null $comment
 * @property-read ChecklistItem $item
 */
class InspectionAnswer extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const EVIDENCE = 'evidence';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['result' => AnswerResult::class];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::EVIDENCE)
            ->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * @return BelongsTo<ChecklistItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }
}
