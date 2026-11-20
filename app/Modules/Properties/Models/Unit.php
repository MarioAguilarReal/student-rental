<?php

namespace App\Modules\Properties\Models;

use App\Modules\Catalog\Models\Amenity;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Enums\UnitType;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A rentable space inside a property, organized as a tree (RN-20).
 *
 * @property int $id
 * @property int $property_id
 * @property int|null $parent_id
 * @property UnitType $type
 * @property string $name
 * @property string|null $description
 * @property int $capacity
 * @property bool $is_rentable
 * @property GenderPolicy|null $gender_policy
 * @property list<string>|null $allowed_communities
 * @property UnitStatus $status
 * @property int $position
 * @property-read Property $property
 * @property-read Unit|null $parent
 */
#[UseFactory(UnitFactory::class)]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'status' => UnitStatus::class,
            'gender_policy' => GenderPolicy::class,
            'allowed_communities' => 'array',
            'is_rentable' => 'boolean',
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
     * @return BelongsTo<Unit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->orderBy('name');
    }

    /**
     * Nearest first. Depth is at most 3, so walking the parents is cheap.
     *
     * @return Collection<int, Unit>
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * @return Collection<int, Unit>
     */
    public function descendants(): Collection
    {
        return $this->children->flatMap(fn (Unit $child) => collect([$child])->merge($child->descendants()));
    }

    /**
     * RN-30: own policy, else the nearest ancestor's, else the property's.
     */
    public function effectiveGenderPolicy(): GenderPolicy
    {
        return $this->gender_policy
            ?? $this->ancestors()->first(fn (Unit $unit) => $unit->gender_policy !== null)->gender_policy
            ?? $this->property->gender_policy;
    }

    /**
     * RN-31: same inheritance as the gender policy. Empty list = everyone.
     *
     * @return list<string>
     */
    public function effectiveAllowedCommunities(): array
    {
        return $this->allowed_communities
            ?? $this->ancestors()->first(fn (Unit $unit) => $unit->allowed_communities !== null)->allowed_communities
            ?? $this->property->allowed_communities;
    }

    /**
     * RN-22: ids of the unit, its ancestors and its descendants (the "family" that shares dates).
     *
     * @return list<int>
     */
    public function familyIds(): array
    {
        return [
            $this->id,
            ...$this->ancestors()->map(fn (Unit $unit): int => $unit->id)->all(),
            ...$this->descendants()->map(fn (Unit $unit): int => $unit->id)->all(),
        ];
    }
}
