<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Pricing\Http\Controllers\AvailabilityBlockController;
use App\Modules\Pricing\Http\Controllers\UnitPricingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('properties/{property}')->name('pricing.')->group(function () {
    Route::put('units/{unit}/pricing', [UnitPricingController::class, 'update'])
        ->middleware('can:'.Permission::RatePlansManage->value)
        ->scopeBindings()
        ->name('units.update');

    Route::middleware('can:'.Permission::AvailabilityBlocksManage->value)->group(function () {
        Route::post('availability-blocks', [AvailabilityBlockController::class, 'store'])->name('blocks.store');
        Route::delete('availability-blocks/{block}', [AvailabilityBlockController::class, 'destroy'])->name('blocks.destroy');
    });
});
