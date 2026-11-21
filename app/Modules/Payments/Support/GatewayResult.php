<?php

namespace App\Modules\Payments\Support;

use App\Modules\Payments\Enums\PaymentAttemptStatus;

/**
 * What a gateway answered for one attempt.
 */
final readonly class GatewayResult
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public PaymentAttemptStatus $status,
        public ?string $reference = null,
        public array $response = [],
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function succeeded(string $reference, array $response = []): self
    {
        return new self(PaymentAttemptStatus::Succeeded, $reference, $response);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function failed(?string $reference, array $response = []): self
    {
        return new self(PaymentAttemptStatus::Failed, $reference, $response);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function processing(string $reference, array $response = []): self
    {
        return new self(PaymentAttemptStatus::Processing, $reference, $response);
    }
}
