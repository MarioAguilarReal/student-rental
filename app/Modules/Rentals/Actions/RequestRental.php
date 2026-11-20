<?php

namespace App\Modules\Rentals\Actions;

use App\Models\User;
use App\Modules\Identity\Actions\DeclareCohabitationGender;
use App\Modules\Identity\Enums\CohabitationGender;
use App\Modules\Payments\Contracts\CommissionRates;
use App\Modules\Pricing\Contracts\UnitAvailability;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Pricing\Models\RatePlan;
use App\Modules\Pricing\Models\UnitDeposit;
use App\Modules\Pricing\Support\RentalPeriods;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Models\Unit;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Events\RentalRequested;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Support\Text;
use App\Modules\Verification\Enums\VerificationKind;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HU-08.01: a verified client requests a space for some dates and a plan.
 *
 * RN-01 current verification · RN-12 only listed properties · RN-30 to RN-32 coexistence ·
 * RN-33 house rules accepted · RN-43 periods · RN-63 dates · RN-22 availability ·
 * RN-64 / D-73 no overlap with the client's own rentals or requests · RN-45 copy of the commercial values ·
 * RN-61 the operator has 72 h to answer. A request does not hold the dates (RN-23).
 */
class RequestRental
{
    public function __construct(
        private UnitAvailability $availability,
        private CommissionRates $commissions,
        private DeclareCohabitationGender $declareGender,
    ) {}

    public function handle(
        User $client,
        Unit $unit,
        RatePeriod $period,
        CarbonImmutable $startsOn,
        int $periods,
        bool $acceptsHouseRules,
        ?string $message = null,
        ?CohabitationGender $gender = null,
    ): Rental {
        $profile = $client->verificationProfile(VerificationKind::Client);

        if (! $profile?->isVerified()) {
            throw new BusinessRuleViolation('RN-01', Text::of('Only clients with a current verification can request a rental.'));
        }

        $property = $unit->property;

        if ($property->status !== PropertyStatus::Published || ! $property->hasValidInspection() || ! $unit->is_rentable || $unit->status !== UnitStatus::Active) {
            throw new BusinessRuleViolation('RN-12', Text::of('This space is not accepting requests.'), 'unit_id');
        }

        $communities = $unit->effectiveAllowedCommunities();

        if ($communities !== [] && ! in_array($profile->community_type?->value, $communities, true)) {
            throw new BusinessRuleViolation('RN-31', Text::of('This space is not open to your community.'), 'unit_id');
        }

        $genderPolicy = $unit->effectiveGenderPolicy();
        $gender ??= $client->cohabitation_gender;

        if ($genderPolicy->isRestricted() && $gender === null) {
            throw new BusinessRuleViolation('RN-32', Text::of('This space has a gender rule: tell us your gender for coexistence to request it.'), 'gender');
        }

        if (! $genderPolicy->admits($gender)) {
            throw new BusinessRuleViolation('RN-30', Text::of('The coexistence rule of this space is: :policy.', ['policy' => mb_strtolower($genderPolicy->label())]), 'gender');
        }

        if (! $acceptsHouseRules) {
            throw new BusinessRuleViolation('RN-33', Text::of('Accept the house rules to send your request.'), 'accept_rules');
        }

        $plan = RatePlan::query()->where('unit_id', $unit->id)->where('period', $period)->where('is_active', true)->first();

        if (! $plan) {
            throw new BusinessRuleViolation('RN-43', Text::of('This space is not offered with that period.'), 'period');
        }

        if ($periods < $plan->min_periods || $periods > $plan->max_periods) {
            throw new BusinessRuleViolation('RN-43', Text::of('For this space choose between :min and :max periods.', ['min' => $plan->min_periods, 'max' => $plan->max_periods]), 'periods');
        }

        $today = CarbonImmutable::today();

        if ($startsOn->lessThanOrEqualTo($today) || $startsOn->greaterThan($today->addMonthsNoOverflow(12))) {
            throw new BusinessRuleViolation('RN-63', Text::of('The move-in date must be from tomorrow and within 12 months.'), 'starts_on');
        }

        $endsOn = RentalPeriods::endDate($startsOn, $period, $periods);

        if (! $this->availability->isAvailable($unit, $startsOn, $endsOn)) {
            throw new BusinessRuleViolation('RN-22', Text::of('The space is not available on those dates.'), 'starts_on');
        }

        $ownOverlap = Rental::query()
            ->where('client_id', $client->id)
            ->overlapping($startsOn, $endsOn)
            ->where(fn ($query) => $query
                ->blocking()
                ->orWhere(fn ($pending) => $pending->where('status', RentalStatus::Requested)->where('unit_id', $unit->id)))
            ->exists();

        if ($ownOverlap) {
            throw new BusinessRuleViolation('RN-64', Text::of('You already have a rental or a request for those dates.'), 'starts_on');
        }

        $commission = $this->commissions->basisPointsFor($property->management_mode);
        $deposit = (int) (UnitDeposit::query()->where('unit_id', $unit->id)->value('amount_minor') ?? 0);

        return DB::transaction(function () use ($client, $unit, $property, $period, $periods, $startsOn, $endsOn, $plan, $deposit, $commission, $message, $gender, $genderPolicy) {
            if ($genderPolicy->isRestricted() && $gender !== null) {
                $this->declareGender->handle($client, $gender);
            }

            $rental = Rental::create([
                'unit_id' => $unit->id,
                'property_id' => $property->id,
                'client_id' => $client->id,
                'period' => $period,
                'periods' => $periods,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'price_minor' => $plan->price_minor,
                'total_minor' => $plan->price_minor * $periods,
                'deposit_minor' => $deposit,
                'currency' => $plan->currency,
                'management_mode' => $property->management_mode,
                'commission_bp' => $commission,
                'status' => RentalStatus::Requested,
                'client_message' => filled($message) ? trim((string) $message) : null,
                'rules_accepted_at' => now(),
                'expires_at' => now()->addHours(Rental::REQUEST_HOURS),
            ]);

            $rental->forceFill(['folio' => sprintf('R-%d-%06d', $rental->created_at->year, $rental->id)])->save();

            RentalRequested::dispatch($rental, $client);

            return $rental;
        });
    }
}
