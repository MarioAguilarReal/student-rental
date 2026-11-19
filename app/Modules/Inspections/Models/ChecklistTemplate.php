<?php

namespace App\Modules\Inspections\Models;

use App\Modules\Inspections\Enums\ChecklistStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $version
 * @property ChecklistStatus $status
 * @property CarbonImmutable|null $published_at
 */
class ChecklistTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => ChecklistStatus::class, 'published_at' => 'immutable_datetime', 'version' => 'integer'];
    }

    /**
     * @return HasMany<ChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position')->orderBy('id');
    }

    public static function current(): ?self
    {
        return self::query()->where('status', ChecklistStatus::Published)->latest('version')->first();
    }
}
