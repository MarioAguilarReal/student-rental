<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Verification\Http\Controllers\ReviewController;
use App\Modules\Verification\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Route names use the plural "verifications." to avoid clashing with Fortify's "verification.*" (email).
Route::middleware(['auth', 'verified'])->name('verifications.')->group(function () {
    Route::middleware('can:'.Permission::VerificationDocumentsUpload->value)->group(function () {
        Route::get('verification', [VerificationController::class, 'show'])->name('show');
        Route::post('verification/client', [VerificationController::class, 'storeClient'])->name('client.store');
        Route::post('verification/owner', [VerificationController::class, 'storeOwner'])->name('owner.store');
    });

    Route::middleware('can:'.Permission::VerificationDocumentsReview->value)
        ->prefix('admin/verifications')
        ->name('reviews.')
        ->group(function () {
            Route::get('/', [ReviewController::class, 'index'])->name('index');
            Route::get('{profile}', [ReviewController::class, 'show'])->name('show');
            Route::post('{profile}/approve', [ReviewController::class, 'approve'])->name('approve');
            Route::post('{profile}/reject', [ReviewController::class, 'reject'])->name('reject');
        });
});
