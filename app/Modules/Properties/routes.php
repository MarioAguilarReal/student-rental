<?php

use App\Modules\Properties\Http\Controllers\PhotoController;
use App\Modules\Properties\Http\Controllers\PropertyController;
use App\Modules\Properties\Http\Controllers\PropertySectionController;
use App\Modules\Properties\Http\Controllers\PropertyStatusController;
use App\Modules\Properties\Http\Controllers\UnitController;
use App\Modules\Properties\Models\Property;
use Illuminate\Support\Facades\Route;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

Route::bind('photo', fn (string $id) => Media::query()->findOrFail($id));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin/properties', [PropertyController::class, 'adminIndex'])
        ->can('viewAll', Property::class)
        ->name('properties.admin.index');

    Route::get('properties', [PropertyController::class, 'index'])->can('create', Property::class)->name('properties.index');
    Route::get('properties/create', [PropertyController::class, 'create'])->can('create', Property::class)->name('properties.create');
    Route::post('properties', [PropertyController::class, 'store'])->can('create', Property::class)->name('properties.store');
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->can('view', 'property')->name('properties.edit');
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');

    Route::prefix('properties/{property}')->name('properties.')->scopeBindings()->group(function () {
        Route::put('location', [PropertySectionController::class, 'location'])->name('location.update');
        Route::put('features', [PropertySectionController::class, 'features'])->name('features.update');
        Route::post('ownership-proof', [PropertySectionController::class, 'ownershipProof'])->name('ownership-proof.store');

        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::post('units/template', [UnitController::class, 'template'])->name('units.template');
        Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        Route::post('photos', [PhotoController::class, 'store'])->name('photos.store');
        Route::put('photos/order', [PhotoController::class, 'reorder'])->name('photos.reorder');
        Route::put('photos/{photo}', [PhotoController::class, 'update'])->name('photos.update');
        Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');

        Route::post('inspection-request', [PropertyStatusController::class, 'inspection'])->name('inspection.request');
        Route::post('publish', [PropertyStatusController::class, 'publish'])->name('publish');
        Route::post('pause', [PropertyStatusController::class, 'pause'])->name('pause');
        Route::post('archive', [PropertyStatusController::class, 'archive'])->name('archive');
    });
});
