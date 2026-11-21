<?php

namespace App\Modules\Payments\Support;

use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Enums\SimulatedOutcome;
use App\Modules\Payments\Jobs\DeliverSimulatedPaymentResult;
use App\Modules\Payments\Models\PaymentSimulationSetting;
use App\Modules\Shared\Money;
use Illuminate\Support\Str;

/**
 * D-05 · RN-72: a test gateway. It approves by default; the Super Admin can make it decline
 * or answer late (HU-09.05). A late answer arrives through a queued job, like a webhook.
 * No real money moves.
 */
final class SimulatedGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'simulated';
    }

    public function charge(string $key, Money $amount, string $description): GatewayResult
    {
        $setting = PaymentSimulationSetting::current();
        $reference = 'SIM-'.Str::upper(Str::ulid()->toBase32());
        $response = ['simulated' => true, 'key' => $key, 'amount' => $amount->jsonSerialize(), 'description' => $description];

        return match ($setting->outcome) {
            SimulatedOutcome::Approve => GatewayResult::succeeded($reference, $response),
            SimulatedOutcome::Decline => GatewayResult::failed($reference, [...$response, 'error' => 'card_declined']),
            SimulatedOutcome::Delay => $this->later($key, $reference, $response, $setting->delay_seconds),
        };
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function later(string $key, string $reference, array $response, int $seconds): GatewayResult
    {
        DeliverSimulatedPaymentResult::dispatch($key, GatewayResult::succeeded($reference, $response))
            ->delay(now()->addSeconds($seconds));

        return GatewayResult::processing($reference, [...$response, 'delayed_seconds' => $seconds]);
    }
}
