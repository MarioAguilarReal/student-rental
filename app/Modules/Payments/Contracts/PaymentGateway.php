<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Support\GatewayResult;
use App\Modules\Shared\Money;

/**
 * D-05: the payment gateway behind an interface. The MVP uses SimulatedGateway; a real
 * gateway is a new implementation, nothing else changes.
 *
 * A gateway may answer right away (succeeded / failed) or later (processing): then it
 * reports the final result for the same key through RecordPaymentResult, like a webhook.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * @param  string  $key  Our idempotency key for the attempt; the gateway echoes it back.
     */
    public function charge(string $key, Money $amount, string $description): GatewayResult;
}
