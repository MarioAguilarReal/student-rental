<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\AmenityCategory;
use App\Modules\Catalog\Enums\AmenityScope;
use Database\Factories\AmenityFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalog entry (HU-03.02). Deactivating hides it for new selections; existing links stay.
 *
 * @property int $id
 * @property string $name
 * @property AmenityCategory $category
 * @property AmenityScope $scope
 * @property bool $is_active
 */
#[UseFactory(AmenityFactory::class)]
class Amenity extends Model
{
    /** @use HasFactory<AmenityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'category', 'scope', 'is_active'];

    protected function casts(): array
    {
        return [
            'category' => AmenityCategory::class,
            'scope' => AmenityScope::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @param  list<AmenityScope>  $scopes
     */
    public function scopeAvailableFor(Builder $query, array $scopes): void
    {
        $query->whereIn('scope', $scopes);
    }
}
