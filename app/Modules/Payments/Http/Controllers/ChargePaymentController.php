<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payments\Actions\PayCharge;
use App\Modules\Payments\Enums\PaymentAttemptStatus;
use App\Modules\Payments\Models\Charge;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * HU-09.01: the client pays a charge with the test gateway.
 */
class ChargePaymentController extends Controller
{
    public function store(Request $request, Rental $rental, Charge $charge, PayCharge $pay): RedirectResponse
    {
        /** @var User $client */
        $client = $request->user();
        $attempt = $pay->handle($client, $charge);

        match ($attempt->status) {
            PaymentAttemptStatus::Succeeded => Toast::success($charge->number === 1
                ? __('Payment received. Your rental is confirmed.')
                : __('Payment received.')),
            PaymentAttemptStatus::Processing => Toast::success(__('Your payment is being processed. We will let you know when it is done.')),
            PaymentAttemptStatus::Failed => Toast::error(__('The payment was declined. You can try again.')),
        };

        return back();
    }
}
