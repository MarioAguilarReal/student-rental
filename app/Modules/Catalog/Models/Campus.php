<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\CampusFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $institution_id
 * @property string $name
 * @property string|null $address
 * @property string $latitude
 * @property string $longitude
 */
#[UseFactory(CampusFactory::class)]
class Campus extends Model
{
    /** @use HasFactory<CampusFactory> */
    use HasFactory;

    protected $table = 'campuses';

    protected $fillable = ['name', 'address', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
