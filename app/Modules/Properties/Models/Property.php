<?php

namespace App\Modules\Properties\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PauseReason;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\PropertyType;
use Carbon\CarbonImmutable;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $owner_id
 * @property int $created_by
 * @property ManagementMode $management_mode
 * @property string $title
 * @property string|null $description
 * @property PropertyType $property_type
 * @property string|null $street
 * @property string|null $exterior_number
 * @property string|null $interior_number
 * @property string|null $neighborhood
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string $country
 * @property string|null $latitude
 * @property string|null $longitude
 * @property PropertyStatus $status
 * @property PauseReason|null $pause_reason
 * @property CarbonImmutable|null $inspection_valid_until
 * @property GenderPolicy $gender_policy
 * @property list<string> $allowed_communities
 * @property CarbonImmutable|null $submitted_for_inspection_at
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $archived_at
 * @property-read User $owner
 */
#[UseFactory(PropertyFactory::class)]
class Property extends Model implements HasMedia
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, InteractsWithMedia;

    public const PHOTOS = 'photos';

    public const OWNERSHIP_PROOF = 'ownership_proof';

    public const MAX_PHOTOS = 30;

    public const MIN_PHOTOS_TO_PUBLISH = 3;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'management_mode' => ManagementMode::class,
            'property_type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'pause_reason' => PauseReason::class,
            'gender_policy' => GenderPolicy::class,
            'allowed_communities' => 'array',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'inspection_valid_until' => 'immutable_date',
            'submitted_for_inspection_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        // D-49 · RN-07: private, only reachable through short-lived signed URLs.
        $this->addMediaCollection(self::OWNERSHIP_PROOF)
            ->useDisk('local')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections(self::PHOTOS)
            ->fit(Fit::Crop, 480, 360)
            ->format('webp');

        $this->addMediaConversion('large')
            ->performOnCollections(self::PHOTOS)
            ->fit(Fit::Max, 1600, 1200)
            ->format('webp');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->orderBy('name');
    }

    /**
     * @return BelongsToMany<HouseRule, $this>
     */
    public function houseRules(): BelongsToMany
    {
        return $this->belongsToMany(HouseRule::class)->withPivot(['is_allowed', 'note'])->orderBy('name');
    }

    public function hasCompleteAddress(): bool
    {
        return filled($this->street) && filled($this->exterior_number) && filled($this->neighborhood)
            && filled($this->city) && filled($this->state) && filled($this->postal_code)
            && $this->latitude !== null && $this->longitude !== null;
    }

    public function hasValidInspection(): bool
    {
        return $this->inspection_valid_until !== null
            && $this->inspection_valid_until->greaterThanOrEqualTo(CarbonImmutable::today());
    }

    /**
     * The address shown to the public is approximate until a rental is confirmed (04 · Propiedad).
     */
    public function approximateLocation(): string
    {
        return collect([$this->neighborhood, $this->city, $this->state])->filter()->implode(', ');
    }

    /**
     * The exact address: only for the operator, the inspector and a client with a confirmed rental.
     */
    public function fullAddress(): string
    {
        return collect([
            trim("{$this->street} {$this->exterior_number}".($this->interior_number ? " int. {$this->interior_number}" : '')),
            $this->neighborhood,
            trim("{$this->postal_code} {$this->city}"),
            $this->state,
        ])->filter()->implode(', ');
    }
}
