<?php

namespace App\Modules\Payments\Jobs;

use App\Modules\Payments\Actions\RecordPaymentResult;
use App\Modules\Payments\Models\PaymentAttempt;
use App\Modules\Payments\Support\GatewayResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * RN-72 "delay" scenario: the simulated gateway's late answer, delivered like a webhook.
 */
class DeliverSimulatedPaymentResult implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $key, public GatewayResult $result) {}

    public function handle(RecordPaymentResult $record): void
    {
        $attempt = PaymentAttempt::query()->find((int) str_replace('PA-', '', $this->key));

        if ($attempt !== null) {
            $record->handle($attempt, $this->result);
        }
    }
}
