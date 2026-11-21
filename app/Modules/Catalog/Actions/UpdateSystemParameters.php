<?php

namespace App\Modules\Catalog\Actions;

use App\Models\User;
use App\Modules\Catalog\Events\SystemParametersUpdated;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use App\Modules\Shared\Models\SystemParameterValue;
use App\Modules\Shared\Support\Parameters;
use App\Modules\Shared\Support\Text;
use Illuminate\Support\Facades\DB;

/**
 * HU-03.05: the Super Admin tunes deadlines, validities and price factors.
 *
 * - Each value stays within its range (SystemParameter::range()).
 * - Changes apply from now on: rentals, verifications and inspections already dated keep
 *   their dates (RN-45).
 * - Only what changed is recorded, with its value before and after (RN-90).
 */
class UpdateSystemParameters
{
    /**
     * @param  array<string, int|float|string>  $values  parameter key => new value
     * @return array<string, int|float> the parameters that changed, with their new values
     */
    public function handle(User $actor, array $values): array
    {
        $before = Parameters::all();
        $changes = [];

        foreach ($values as $key => $raw) {
            $parameter = SystemParameter::tryFrom($key);

            if ($parameter === null || ! is_numeric($raw)) {
                continue;
            }

            [$min, $max] = $parameter->range();
            $value = $parameter->isDecimal() ? round((float) $raw, 2) : (int) $raw;

            if ($value < $min || $value > $max || (! $parameter->isDecimal() && (float) $raw !== (float) (int) $raw)) {
                throw new BusinessRuleViolation('HU-03.05', Text::of(':parameter must be between :min and :max.', [
                    'parameter' => $parameter->label(), 'min' => $min, 'max' => $max,
                ]), "values.{$key}");
            }

            if ($value != $before[$key]) {
                $changes[$key] = $value;
            }
        }

        if ($changes === []) {
            return [];
        }

        DB::transaction(function () use ($actor, $changes) {
            foreach ($changes as $key => $value) {
                SystemParameterValue::query()->updateOrCreate(['key' => $key], ['value' => (string) $value, 'updated_by' => $actor->id]);
            }
        });

        Parameters::forget();

        SystemParametersUpdated::dispatch($actor, array_intersect_key($before, $changes), $changes);

        return $changes;
    }
}
