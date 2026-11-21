<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payments\Support\FinanceReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-09.03: the owner's income from rent: gross, commission and net.
 */
class BalanceController extends Controller
{
    public function show(Request $request, FinanceReport $report): Response
    {
        /** @var User $owner */
        $owner = $request->user();

        return Inertia::render('payments/balance', $report->ownerBalance($owner));
    }
}
