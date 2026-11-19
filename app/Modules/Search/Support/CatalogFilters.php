<?php

namespace App\Modules\Search\Support;

use App\Modules\Catalog\Models\Campus;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitType;
use Carbon\CarbonImmutable;

/**
 * HU-07.01 filters, already parsed. Prices are in cents.
 */
final readonly class CatalogFilters
{
    /**
     * @param  list<int>  $amenityIds
     */
    public function __construct(
        public RatePeriod $period = RatePeriod::Monthly,
        public ?Campus $campus = null,
        public float $radiusKm = 5.0,
        public ?UnitType $unitType = null,
        public ?int $minPriceMinor = null,
        public ?int $maxPriceMinor = null,
        public ?CarbonImmutable $moveIn = null,
        public array $amenityIds = [],
        public ?GenderPolicy $genderPolicy = null,
        public string $sort = 'recommended',
    ) {}
}
