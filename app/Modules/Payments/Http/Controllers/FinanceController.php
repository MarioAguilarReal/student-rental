<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Support\FinanceReport;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-09.04: commissions, balances owed to owners, deposits held and overdue charges.
 */
class FinanceController extends Controller
{
    public function index(FinanceReport $report): Response
    {
        return Inertia::render('payments/finance', $report->panel());
    }
}
