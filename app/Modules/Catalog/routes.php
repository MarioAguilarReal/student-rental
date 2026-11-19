<?php

use App\Modules\Catalog\Http\Controllers\AmenityController;
use App\Modules\Catalog\Http\Controllers\CampusController;
use App\Modules\Catalog\Http\Controllers\HouseRuleController;
use App\Modules\Catalog\Http\Controllers\InstitutionController;
use App\Modules\Identity\Enums\Permission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:'.Permission::CatalogManage->value])
    ->prefix('admin')
    ->name('catalog.')
    ->group(function () {
        Route::resource('institutions', InstitutionController::class)->except(['show', 'destroy']);
        Route::resource('amenities', AmenityController::class)->only(['index', 'store', 'update']);
        Route::resource('house-rules', HouseRuleController::class)->only(['index', 'store', 'update']);

        Route::scopeBindings()->group(function () {
            Route::post('institutions/{institution}/campuses', [CampusController::class, 'store'])
                ->name('institutions.campuses.store');
            Route::put('institutions/{institution}/campuses/{campus}', [CampusController::class, 'update'])
                ->name('institutions.campuses.update');
            Route::delete('institutions/{institution}/campuses/{campus}', [CampusController::class, 'destroy'])
                ->name('institutions.campuses.destroy');
        });
    });
