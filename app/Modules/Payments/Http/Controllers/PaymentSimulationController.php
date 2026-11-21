<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payments\Actions\ConfigurePaymentSimulation;
use App\Modules\Payments\Enums\SimulatedOutcome;
use App\Modules\Payments\Http\Requests\PaymentSimulationRequest;
use App\Modules\Payments\Models\PaymentSimulationSetting;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-09.05 · RN-72: what the test gateway answers.
 */
class PaymentSimulationController extends Controller
{
    public function edit(): Response
    {
        $setting = PaymentSimulationSetting::current();

        return Inertia::render('payments/simulation', [
            'outcome' => $setting->outcome->value,
            'delaySeconds' => $setting->delay_seconds,
            'outcomes' => SimulatedOutcome::options(),
        ]);
    }

    public function update(PaymentSimulationRequest $request, ConfigurePaymentSimulation $configure): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $configure->handle(
            $actor,
            $request->enum('outcome', SimulatedOutcome::class) ?? SimulatedOutcome::Approve,
            $request->integer('delay_seconds'),
        );

        Toast::success(__('Test payments will follow the new scenario.'));

        return back();
    }
}
