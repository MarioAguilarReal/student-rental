<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\Enums\RatePeriod;

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
        $config = config('pricing.suggestions');
        $round = fn (float $amount): int => (int) (round($amount / $config['round_to_minor']) * $config['round_to_minor']);

        return [
            RatePeriod::Weekly->value => $round($monthlyMinor / 4 * $config['weekly_factor']),
            RatePeriod::Yearly->value => $round($monthlyMinor * 12 * $config['yearly_factor']),
        ];
    }
}
