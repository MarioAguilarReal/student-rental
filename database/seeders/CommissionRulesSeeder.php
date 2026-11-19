<?php

namespace Database\Seeders;

use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Properties\Enums\ManagementMode;
use Illuminate\Database\Seeder;

/**
 * RN-50 / D-09: 10 % self-managed, 20 % managed. Idempotent; runs in every environment.
 */
class CommissionRulesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([[ManagementMode::SelfManaged, 1000], [ManagementMode::Managed, 2000]] as [$mode, $basisPoints]) {
            CommissionRule::query()->firstOrCreate(
                ['management_mode' => $mode, 'valid_until' => null],
                ['percentage_bp' => $basisPoints, 'valid_from' => '2026-01-01'],
            );
        }
    }
}
