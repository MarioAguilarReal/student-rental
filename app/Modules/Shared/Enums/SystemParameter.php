<?php

namespace App\Modules\Shared\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * HU-03.05: deadlines, validities and factors the Super Admin can tune without a deploy.
 * Each module reads its own through App\Modules\Shared\Support\Parameters; the defaults are
 * the values in docs/spec/03-reglas-negocio.md.
 */
enum SystemParameter: string
{
    use HasLabel;

    case RentalRequestHours = 'rentals.request_hours';
    case RentalPaymentHours = 'rentals.payment_hours';
    case VerificationStudentMonths = 'verification.student_months';
    case VerificationFacultyMonths = 'verification.faculty_months';
    case VerificationExchangeMaxMonths = 'verification.exchange_max_months';
    case VerificationReminderDays = 'verification.reminder_days';
    case InspectionValidityMonths = 'inspections.validity_months';
    case InspectionRenewalDays = 'inspections.renewal_days';
    case ChargeReminderDays = 'payments.reminder_days';
    case WeeklyPriceFactor = 'pricing.weekly_factor';
    case YearlyPriceFactor = 'pricing.yearly_factor';

    public function default(): int|float
    {
        return match ($this) {
            self::RentalRequestHours => 72,          // RN-61
            self::RentalPaymentHours => 48,          // RN-62
            self::VerificationStudentMonths => 6,    // RN-04
            self::VerificationFacultyMonths => 12,   // RN-04
            self::VerificationExchangeMaxMonths => 12, // RN-04
            self::VerificationReminderDays => 15,    // RN-04
            self::InspectionValidityMonths => 12,    // RN-83
            self::InspectionRenewalDays => 30,       // RN-85
            self::ChargeReminderDays => 3,           // HU-10.01
            self::WeeklyPriceFactor => 1.15,         // RN-41
            self::YearlyPriceFactor => 0.90,         // RN-41
        };
    }

    /**
     * @return 'hours'|'days'|'months'|'factor'
     */
    public function unit(): string
    {
        return match ($this) {
            self::RentalRequestHours, self::RentalPaymentHours => 'hours',
            self::VerificationReminderDays, self::InspectionRenewalDays, self::ChargeReminderDays => 'days',
            self::WeeklyPriceFactor, self::YearlyPriceFactor => 'factor',
            default => 'months',
        };
    }

    public function isDecimal(): bool
    {
        return $this->unit() === 'factor';
    }

    /**
     * @return array{0: int|float, 1: int|float}
     */
    public function range(): array
    {
        return match ($this->unit()) {
            'hours' => [1, 336],
            'days' => [1, 90],
            'months' => [1, 36],
            'factor' => [0.5, 2.0],
        };
    }

    /**
     * The area of the platform it belongs to, for grouping on screen.
     */
    public function area(): string
    {
        return strstr($this->value, '.', true) ?: $this->value;
    }
}
