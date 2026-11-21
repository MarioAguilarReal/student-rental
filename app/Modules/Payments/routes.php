<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Payments\Http\Controllers\BalanceController;
use App\Modules\Payments\Http\Controllers\ChargePaymentController;
use App\Modules\Payments\Http\Controllers\CommissionRuleController;
use App\Modules\Payments\Http\Controllers\FinanceController;
use App\Modules\Payments\Http\Controllers\PaymentSimulationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // HU-09.01: clients pay their own charges
    Route::post('rentals/{rental}/charges/{charge}/pay', [ChargePaymentController::class, 'store'])
        ->scopeBindings()->can('pay', 'charge')->name('charges.pay');

    // HU-09.03: owners see their income
    Route::get('income', [BalanceController::class, 'show'])
        ->middleware(['can:'.Permission::BalancesView->value, 'can:'.Permission::PropertiesCreate->value])
        ->name('payments.balance');

    Route::prefix('admin')->group(function () {
        // HU-09.04: administrators
        Route::get('finance', [FinanceController::class, 'index'])
            ->middleware(['can:'.Permission::BalancesView->value, 'can:'.Permission::PropertiesViewAny->value])
            ->name('payments.finance');

        // HU-03.04 and HU-09.05: Super Admin
        Route::middleware('can:'.Permission::CommissionRulesManage->value)->group(function () {
            Route::get('commission-rules', [CommissionRuleController::class, 'index'])->name('commission-rules.index');
            Route::post('commission-rules', [CommissionRuleController::class, 'store'])->name('commission-rules.store');
            Route::delete('commission-rules/{commissionRule}', [CommissionRuleController::class, 'destroy'])->name('commission-rules.destroy');
        });

        Route::middleware('can:'.Permission::PaymentSimulationConfigure->value)->group(function () {
            Route::get('payment-simulation', [PaymentSimulationController::class, 'edit'])->name('payment-simulation.edit');
            Route::put('payment-simulation', [PaymentSimulationController::class, 'update'])->name('payment-simulation.update');
        });
    });
});
