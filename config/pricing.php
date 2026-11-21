<?php

/*
| RN-41: suggested prices from the monthly price. The factors are system parameters
| (HU-03.05, App\Modules\Shared\Enums\SystemParameter); only the rounding lives here.
*/

return [
    'suggestions' => [
        'round_to_minor' => 1000, // $10.00
    ],
];
