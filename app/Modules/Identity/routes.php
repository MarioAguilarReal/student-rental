<?php

use App\Models\User;
use App\Modules\Identity\Http\Controllers\AccountProfileController;
use App\Modules\Identity\Http\Controllers\UserController;
use App\Modules\Identity\Http\Controllers\UserRoleController;
use App\Modules\Identity\Http\Controllers\UserSuspensionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('settings/account-profiles', [AccountProfileController::class, 'store'])->name('account-profiles.store');

    Route::prefix('admin')->name('users.')->group(function () {
        Route::get('users', [UserController::class, 'index'])->can('viewAny', User::class)->name('index');
        Route::get('users/{user}', [UserController::class, 'show'])->can('view', 'user')->name('show');
        Route::put('users/{user}/roles', [UserRoleController::class, 'update'])->name('roles.update');
        Route::post('users/{user}/suspension', [UserSuspensionController::class, 'store'])->name('suspension.store');
        Route::delete('users/{user}/suspension', [UserSuspensionController::class, 'destroy'])->name('suspension.destroy');
    });
});
