<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Inspections\Actions\AssignInspection;
use App\Modules\Inspections\Enums\InspectionStatus;
use App\Modules\Inspections\Models\Inspection;
use App\Modules\Pricing\Actions\SaveUnitPricing;
use App\Modules\Properties\Actions\SubmitPropertyForInspection;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;
use Illuminate\Database\Seeder;

/**
 * Puts the demo property one step from publication: owner verified, proof attached, monthly
 * prices, inspection requested and assigned to the development inspector for today. Local only.
 */
class DevelopmentInspectionSeeder extends Seeder
{
    public function run(SaveUnitPricing $savePricing, SubmitPropertyForInspection $submit, AssignInspection $assign): void
    {
        $owner = User::query()->where('email', 'owner@example.test')->first();
        $admin = User::query()->where('email', 'admin@example.test')->first();
        $inspector = User::query()->where('email', 'inspector@example.test')->first();
        $property = $owner ? Property::query()->where('owner_id', $owner->id)->where('status', PropertyStatus::Draft)->oldest('id')->first() : null;

        if (! $owner || ! $admin || ! $inspector || ! $property || Inspection::query()->where('property_id', $property->id)->exists()) {
            return;
        }

        $owner->verificationProfiles()->where('kind', VerificationKind::Owner)
            ->update(['status' => VerificationStatus::Verified, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $owner->unsetRelation('verificationProfiles');

        $property->addMediaFromString("%PDF-1.4\n% Comprobante de propiedad de prueba\n")
            ->usingFileName('escritura-demo.pdf')
            ->toMediaCollection(Property::OWNERSHIP_PROOF);

        foreach ($property->units()->where('is_rentable', true)->get() as $unit) {
            $monthly = $unit->parent_id === null ? 1_200_000 : 450_000;
            $savePricing->handle($owner, $unit, [
                'monthly' => ['enabled' => true, 'price_minor' => $monthly, 'min_periods' => 1, 'max_periods' => 12],
            ], $monthly);
        }

        $submit->handle($property->fresh() ?? $property);

        $inspection = Inspection::query()->where('property_id', $property->id)->where('status', InspectionStatus::PendingAssignment)->firstOrFail();
        $assign->handle($admin, $inspection, $inspector, now()->toImmutable()->startOfDay());
    }
}
