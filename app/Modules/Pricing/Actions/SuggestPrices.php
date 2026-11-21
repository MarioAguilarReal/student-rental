<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;

/**
 * RN-41: suggestions from the monthly price — weekly ≈ monthly ÷ 4 × 1.15, yearly ≈ monthly
 * × 12 × 0.90 — rounded to $10. The owner may change them.
 */
class SuggestPrices
{
    /**
     * @return array{weekly: int, yearly: int}
     */
    public function handle(int $monthlyMinor): array
    {
        $config = self::config();
        $round = fn (float $amount): int => (int) (round($amount / $config['round_to_minor']) * $config['round_to_minor']);

        return [
            RatePeriod::Weekly->value => $round($monthlyMinor / 4 * $config['weekly_factor']),
            RatePeriod::Yearly->value => $round($monthlyMinor * 12 * $config['yearly_factor']),
        ];
    }

    /**
     * RN-41: the factors are system parameters (HU-03.05); the rounding stays in config.
     *
     * @return array{weekly_factor: float, yearly_factor: float, round_to_minor: int}
     */
    public static function config(): array
    {
        return [
            'weekly_factor' => Parameters::decimal(SystemParameter::WeeklyPriceFactor),
            'yearly_factor' => Parameters::decimal(SystemParameter::YearlyPriceFactor),
            'round_to_minor' => (int) config('pricing.suggestions.round_to_minor', 1000),
        ];
    }
}
