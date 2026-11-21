<?php

namespace App\Modules\Payments\Http\Requests;

use App\Modules\Payments\Actions\ConfigurePaymentSimulation;
use App\Modules\Payments\Enums\SimulatedOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentSimulationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::enum(SimulatedOutcome::class)],
            'delay_seconds' => ['required', 'integer', 'min:1', 'max:'.ConfigurePaymentSimulation::MAX_DELAY_SECONDS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['outcome' => __('result'), 'delay_seconds' => __('delay')];
    }
}
