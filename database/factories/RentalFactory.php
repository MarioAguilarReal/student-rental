<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    protected $model = Rental::class;

    public function definition(): array
    {
        $start = now()->addDays(10)->startOfDay();

        return [
            'unit_id' => Unit::factory(),
            'property_id' => fn (array $attributes) => Unit::query()->whereKey($attributes['unit_id'])->value('property_id'),
            'client_id' => User::factory(),
            'period' => RatePeriod::Monthly,
            'periods' => 1,
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addMonthNoOverflow()->toDateString(),
            'price_minor' => 450_000,
            'total_minor' => 450_000,
            'deposit_minor' => 450_000,
            'currency' => 'MXN',
            'management_mode' => ManagementMode::SelfManaged,
            'commission_bp' => 1000,
            'status' => RentalStatus::Requested,
            'rules_accepted_at' => now(),
            'expires_at' => now()->addHours(Rental::requestHours()),
        ];
    }

    public function between(string $startsOn, string $endsOn): static
    {
        return $this->state(['starts_on' => $startsOn, 'ends_on' => $endsOn]);
    }

    public function status(RentalStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'expires_at' => match ($status) {
                RentalStatus::Requested => now()->addHours(Rental::requestHours()),
                RentalStatus::Approved => now()->addHours(Rental::paymentHours()),
                default => null,
            },
            'decided_at' => $status === RentalStatus::Requested ? null : now(),
            'confirmed_at' => in_array($status, [RentalStatus::Confirmed, RentalStatus::Active, RentalStatus::Completed], true) ? now() : null,
        ]);
    }
}
