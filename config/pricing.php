<?php

/*
| RN-41: suggested prices from the monthly price. Adjustable (HU-03.05 will expose them).
*/

return [
    'suggestions' => [
        'weekly_factor' => (float) env('PRICING_WEEKLY_FACTOR', 1.15), // monthly ÷ 4 × factor
        'yearly_factor' => (float) env('PRICING_YEARLY_FACTOR', 0.90), // monthly × 12 × factor
        'round_to_minor' => 1000, // $10.00
    ],
];
