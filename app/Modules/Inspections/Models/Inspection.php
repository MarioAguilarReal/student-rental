<?php

namespace App\Modules\Inspections\Models;

use App\Models\User;
use App\Modules\Inspections\Enums\InspectionResult;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Properties\Models\Property;
use Carbon\CarbonImmutable;
use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $property_id
 * @property InspectionType $type
 * @property int $checklist_template_id
 * @property int|null $inspector_id
 * @property int|null $assigned_by
 * @property CarbonImmutable|null $scheduled_for
 * @property InspectionStatus $status
 * @property InspectionResult|null $result
 * @property string|null $observations
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $valid_until
 * @property-read Property $property
 * @property-read ChecklistTemplate $template
 * @property-read User|null $inspector
 */
#[UseFactory(InspectionFactory::class)]
class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => InspectionType::class,
            'status' => InspectionStatus::class,
            'result' => InspectionResult::class,
            'scheduled_for' => 'immutable_date',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'valid_until' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * @return HasMany<InspectionAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(InspectionAnswer::class);
    }
}
