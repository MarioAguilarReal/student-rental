<?php

use App\Modules\Rentals\Http\Controllers\ClientRentalsController;
use App\Modules\Rentals\Http\Controllers\ManageRentalsController;
use App\Modules\Rentals\Http\Controllers\RentalController;
use App\Modules\Rentals\Models\Rental;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // HU-08.01 / HU-08.04: clients
    Route::post('stays/{property}/rentals', [ClientRentalsController::class, 'store'])->can('create', Rental::class)->middleware('throttle:sensitive')->name('rentals.store');
    Route::get('rentals', [ClientRentalsController::class, 'index'])->can('create', Rental::class)->name('rentals.index');

    // HU-08.05: owners and administrators
    Route::get('manage/rentals', [ManageRentalsController::class, 'index'])->can('viewAny', Rental::class)->name('rentals.manage');

    // Shared detail; each action authorizes its own role (HU-08.02, HU-08.07)
    Route::get('rentals/{rental}', [RentalController::class, 'show'])->can('view', 'rental')->name('rentals.show');
    Route::post('rentals/{rental}/approve', [RentalController::class, 'approve'])->can('decide', 'rental')->name('rentals.approve');
    Route::post('rentals/{rental}/reject', [RentalController::class, 'reject'])->can('decide', 'rental')->name('rentals.reject');
    Route::post('rentals/{rental}/cancel', [RentalController::class, 'cancel'])->can('cancel', 'rental')->name('rentals.cancel');
});
